<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Quonain\SmartResponse\Builders\ApiResponseBuilder;
use Quonain\SmartResponse\Builders\WebResponseBuilder;
use Quonain\SmartResponse\Contracts\RequestDetectorInterface;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;
use Quonain\SmartResponse\Events\SmartResponsePrepared;
use Quonain\SmartResponse\Events\SmartResponsePreparing;
use Quonain\SmartResponse\Support\MessageTranslator;
use Quonain\SmartResponse\Support\MetaEnricher;
use Quonain\SmartResponse\Support\PaginationTransformer;
use Quonain\SmartResponse\Support\ValidationErrorFormatter;
use Quonain\SmartResponse\Support\SmartResponseBuilder;
use Quonain\SmartResponse\Support\CachedResponse;
use Quonain\SmartResponse\Http\Client\OutboundClient;
use Quonain\SmartResponse\Http\Client\OutboundRequest;

final class SmartResponseManager implements SmartResponseManagerInterface
{
    public function __construct(
        private readonly RequestDetectorInterface $detector,
        private readonly ApiResponseBuilder $apiBuilder,
        private readonly WebResponseBuilder $webBuilder,
        private readonly PaginationTransformer $pagination,
        private readonly ValidationErrorFormatter $validationFormatter,
        private readonly MessageTranslator $translator,
        private readonly MetaEnricher $metaEnricher,
        private readonly ?CacheRepository $cache,
        private readonly ?Dispatcher $events,
        private readonly OutboundClient $outboundClient,
        /** @var array<string, mixed> */
        private readonly array $config,
    ) {}

    public function respond(SmartResponsePayload $payload, ?Request $request = null): Response
    {
        $startedAt = microtime(true);
        $request ??= $payload->request ?? request();

        $payload = $payload->withRequest($request);

        $payload = $this->applyProfile($payload);
        $payload = $this->applyTranslations($payload);
        $payload = $this->applyPagination($payload);
        $payload = $this->applyFormatDetection($payload, $request);

        if ($this->detector->expectsApi($request)) {
            $payload = $this->metaEnricher->enrich($payload, $request);
        }

        $this->dispatchPreparing($payload);

        if ($this->shouldUseCache($payload)) {
            $cached = $this->getCachedResponse($payload);

            if ($cached !== null) {
                $cached->headers->set('X-Cache', 'HIT');
                return $cached;
            }
        }

        $response = $this->buildResponse($payload, $request);

        $this->applyCacheHeaders($response);

        $this->logResponse($payload, $request, $startedAt, $response);
        $this->dispatchPrepared($payload, $response);
        $this->storeCachedResponse($payload, $response);

        return $response;
    }

    public function make(mixed $data = null): SmartResponseBuilder
    {
        return new SmartResponseBuilder($this, $data);
    }

    /** @param array<string, mixed> $meta */
    public function success(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
    ): Response {
        return $this->respond(new SmartResponsePayload(
            data: $data,
            message: $message,
            success: true,
            meta: $meta,
            status: $status,
        ));
    }

    /** @param array<string, mixed> $meta */
    public function error(
        ?string $message = null,
        mixed $errors = null,
        int $status = 400,
        array $meta = [],
    ): Response {
        return $this->respond(new SmartResponsePayload(
            message: $message,
            success: false,
            errors: $errors,
            meta: $meta,
            status: $status,
        ));
    }

    public function validationError(
        mixed $errors,
        ?string $message = null,
        int $status = 422,
    ): Response {
        $formatted = $this->validationFormatter->format($errors);

        return $this->respond(new SmartResponsePayload(
            message: $message ?? 'Validation failed',
            success: false,
            errors: $formatted,
            status: $status,
        ));
    }

    /** @param array<string, mixed> $meta */
    public function created(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
    ): Response {
        return $this->success(
            $data,
            $message,
            $meta,
            (int) ($this->config['status_codes']['created'] ?? 201),
        );
    }

    public function noContent(): Response
    {
        $status = (int) ($this->config['status_codes']['no_content'] ?? 204);

        $request = request();

        if ($this->detector->expectsApi($request)) {
            return new Response('', $status, ['Content-Type' => 'application/json']);
        }

        return $this->respond(new SmartResponsePayload(
            success: true,
            status: $status,
        ));
    }

    public function notFound(?string $message = null, mixed $errors = null): Response
    {
        return $this->error(
            $message ?? 'Resource not found',
            $errors,
            (int) ($this->config['status_codes']['not_found'] ?? 404),
        );
    }

    public function unauthorized(?string $message = null, mixed $errors = null): Response
    {
        return $this->error(
            $message ?? 'Unauthorized',
            $errors,
            (int) ($this->config['status_codes']['unauthorized'] ?? 401),
        );
    }

    public function forbidden(?string $message = null, mixed $errors = null): Response
    {
        return $this->error(
            $message ?? 'Forbidden',
            $errors,
            (int) ($this->config['status_codes']['forbidden'] ?? 403),
        );
    }

    private function buildResponse(SmartResponsePayload $payload, Request $request): Response
    {
        if ($this->detector->expectsApi($request) || in_array($payload->format, $this->apiFormats(), true)) {
            return $payload->success
                ? $this->apiBuilder->success($payload)
                : $this->apiBuilder->error($payload);
        }

        if ($payload->redirect !== null || $payload->route !== null) {
            return $this->webBuilder->redirect($payload);
        }

        return $this->webBuilder->view($payload);
    }

    private function applyTranslations(SmartResponsePayload $payload): SmartResponsePayload
    {
        return new SmartResponsePayload(
            request: $payload->request,
            data: $payload->data,
            view: $payload->view,
            viewData: $payload->viewData,
            message: $this->translator->translate($payload->message, $payload->locale),
            success: $payload->success,
            errors: $payload->errors,
            meta: $payload->meta,
            status: $payload->status,
            redirect: $payload->redirect,
            route: $payload->route,
            routeParameters: $payload->routeParameters,
            format: $payload->format,
            profile: $payload->profile,
            locale: $payload->locale,
            flash: $payload->flash,
            toast: $payload->toast,
            cacheKey: $payload->cacheKey,
            cacheTtl: $payload->cacheTtl,
            headers: $payload->headers,
            inertiaComponent: $payload->inertiaComponent,
            useInertia: $payload->useInertia,
            useLivewire: $payload->useLivewire,
        );
    }

    private function applyPagination(SmartResponsePayload $payload): SmartResponsePayload
    {
        $transformed = $this->pagination->transform($payload->data);

        if ($transformed['meta'] === []) {
            return $payload;
        }

        return $payload->replicate(
            data: $transformed['data'],
            meta: array_merge($payload->meta, $transformed['meta']),
        );
    }

    private function applyFormatDetection(SmartResponsePayload $payload, Request $request): SmartResponsePayload
    {
        if ($payload->format !== null) {
            return $payload;
        }

        // A normal browser request must remain a web response. The configured
        // JSON default is only an API fallback, not a signal to convert views.
        if (! $this->detector->expectsApi($request)) {
            return $payload;
        }

        return $payload->replicate(format: $this->detector->getPreferredFormat($request));
    }

    private function applyProfile(SmartResponsePayload $payload): SmartResponsePayload
    {
        if ($payload->profile === null) {
            return $payload;
        }

        $profile = $this->config['profiles'][$payload->profile] ?? null;

        if (! is_array($profile)) {
            return $payload;
        }

        return $payload->replicate(
            format: $payload->format ?? ($profile['format'] ?? null),
            meta: array_merge($profile['meta'] ?? [], $payload->meta),
            headers: array_merge($profile['headers'] ?? [], $payload->headers ?? []),
        );
    }

    /** @return list<string> */
    private function apiFormats(): array
    {
        return $this->config['api_formats'] ?? ['json', 'xml', 'legacy', 'graphql', 'soap'];
    }

    private function shouldUseCache(SmartResponsePayload $payload): bool
    {
        $request = $payload->request;

        if ($request === null || ! $this->detector->expectsApi($request)) {
            return false;
        }

        if (strtoupper($request->method()) !== 'GET') {
            return false;
        }

        if (! ($this->config['cache']['enabled'] ?? false) || $this->cache === null) {
            return false;
        }

        if ($this->hasDynamicMeta()) {
            return false;
        }

        if ($payload->success !== true || ! in_array($payload->status, $this->cacheableStatuses(), true)) {
            return false;
        }

        // A response that identifies a user is private by default. Developers
        // must make the deliberate opt-in and provide an isolated cache key.
        if ($request->user() !== null || $request->bearerToken() !== null) {
            if (! ($this->config['cache']['cache_authenticated'] ?? false) || $payload->cacheKey === null) {
                return false;
            }
        }

        return true;
    }

    private function getCachedResponse(SmartResponsePayload $payload): ?Response
    {
        $key = $this->cacheKey($payload);

        if ($key === null || $this->cache === null) {
            return null;
        }

        $cached = $this->cache->get($key);

        return $cached instanceof CachedResponse ? $cached->toResponse() : null;
    }

    private function storeCachedResponse(SmartResponsePayload $payload, Response $response): void
    {
        if (! $this->shouldUseCache($payload) || $this->cache === null) {
            return;
        }

        $key = $this->cacheKey($payload);

        if ($key === null) {
            return;
        }

        $ttl = $payload->cacheTtl ?? (int) ($this->config['cache']['ttl'] ?? 60);
        if ($ttl <= 0) {
            return;
        }

        $this->cache->put($key, CachedResponse::fromResponse($response), $ttl);
    }

    private function cacheKey(SmartResponsePayload $payload): ?string
    {
        $prefix = $this->config['cache']['prefix'] ?? 'smart_response';
        $cacheKey = $payload->cacheKey;

        if ($cacheKey === null) {
            $request = $payload->request;

            if ($request === null) {
                return null;
            }

            $vary = [];
            foreach (($this->config['cache']['vary_headers'] ?? ['Accept']) as $header) {
                if (is_string($header)) {
                    $vary[$header] = $request->header($header, '');
                }
            }

            $cacheKey = hash('sha256', json_encode([
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'format' => $payload->format,
                'vary' => $vary,
            ], JSON_THROW_ON_ERROR));
        }

        return "{$prefix}:{$cacheKey}";
    }

    public function request(string $provider): OutboundRequest
    {
        return $this->outboundClient->request($provider);
    }

    /** @return list<int> */
    private function cacheableStatuses(): array
    {
        return array_values(array_filter(
            $this->config['cache']['cacheable_statuses'] ?? [200],
            static fn (mixed $status): bool => is_int($status),
        ));
    }

    private function hasDynamicMeta(): bool
    {
        if (! ($this->config['meta']['enabled'] ?? true)) {
            return false;
        }

        return ($this->config['meta']['include_timestamp'] ?? true)
            || ($this->config['meta']['include_request_id'] ?? true);
    }

    private function logResponse(SmartResponsePayload $payload, Request $request, float $startedAt, Response $response): void
    {
        if (! ($this->config['logging']['enabled'] ?? false)) {
            return;
        }

        Log::channel($this->config['logging']['channel'] ?? null)->log(
            $this->config['logging']['level'] ?? 'info',
            'SmartResponse',
            [
                'request_id' => $request->header((string) ($this->config['meta']['request_id_header'] ?? 'X-Request-Id')),
                'trace_id' => $request->header('X-Trace-Id') ?? $request->header('traceparent'),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'format' => $payload->format,
                'success' => $payload->success,
                'status' => $payload->status,
                'message' => $payload->message,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                'cache' => $response->headers->get('X-Cache', 'BYPASS'),
                'error_code' => is_array($payload->errors) ? ($payload->errors['error_code'] ?? null) : null,
            ],
        );
    }

    private function applyCacheHeaders(Response $response): void
    {
        if (! (($this->config['cache']['headers'] ?? true) || ($this->config['cache']['etag'] ?? true))) return;
        $content = $response->getContent() ?: '';
        if ($this->config['cache']['etag'] ?? true) $response->headers->set('ETag', '"'.hash('sha256', $content).'"');
        if ($this->config['cache']['last_modified'] ?? true) $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s').' GMT');
        if ($this->config['cache']['headers'] ?? true) $response->headers->set('X-Cache', 'MISS');
    }

    private function dispatchPreparing(SmartResponsePayload $payload): void
    {
        if (! ($this->config['events']['enabled'] ?? true) || $this->events === null) {
            return;
        }

        $this->events->dispatch(new SmartResponsePreparing($payload));
    }

    private function dispatchPrepared(SmartResponsePayload $payload, Response $response): void
    {
        if (! ($this->config['events']['enabled'] ?? true) || $this->events === null) {
            return;
        }

        $this->events->dispatch(new SmartResponsePrepared($payload, $response));
    }
}
