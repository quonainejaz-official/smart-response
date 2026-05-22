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
        private readonly array $config,
    ) {}

    public function respond(SmartResponsePayload $payload, ?Request $request = null): Response
    {
        $request ??= $payload->request ?? request();

        $payload = $payload->withRequest($request);

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
                return $cached;
            }
        }

        $response = $this->buildResponse($payload, $request);

        $this->logResponse($payload);
        $this->dispatchPrepared($payload, $response);
        $this->storeCachedResponse($payload, $response);

        return $response;
    }

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
            status: $status ?? (int) ($this->config['status_codes']['validation_error'] ?? 422),
        ));
    }

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

        if ($request !== null && $this->detector->expectsApi($request)) {
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
        if ($this->detector->expectsApi($request)) {
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

        return new SmartResponsePayload(
            request: $payload->request,
            data: $payload->data,
            view: $payload->view,
            viewData: $payload->viewData,
            message: $payload->message,
            success: $payload->success,
            errors: $payload->errors,
            meta: $payload->meta,
            status: $payload->status,
            redirect: $payload->redirect,
            route: $payload->route,
            routeParameters: $payload->routeParameters,
            format: $this->detector->getPreferredFormat($request),
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

    private function shouldUseCache(SmartResponsePayload $payload): bool
    {
        $request = $payload->request;

        if ($request === null || ! $this->detector->expectsApi($request)) {
            return false;
        }

        if (strtoupper($request->method()) !== 'GET') {
            return false;
        }

        return ($this->config['cache']['enabled'] ?? false)
            && $this->cache !== null;
    }

    private function getCachedResponse(SmartResponsePayload $payload): ?Response
    {
        $key = $this->cacheKey($payload);

        if ($key === null || $this->cache === null) {
            return null;
        }

        $cached = $this->cache->get($key);

        return $cached instanceof Response ? $cached : null;
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
        $this->cache->put($key, $response, $ttl);
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

            $cacheKey = sha1($request->fullUrl().'|'.$request->header('Accept', ''));
        }

        return "{$prefix}:{$cacheKey}";
    }

    private function logResponse(SmartResponsePayload $payload): void
    {
        if (! ($this->config['logging']['enabled'] ?? false)) {
            return;
        }

        Log::channel($this->config['logging']['channel'] ?? null)->log(
            $this->config['logging']['level'] ?? 'info',
            'SmartResponse',
            [
                'success' => $payload->success,
                'status' => $payload->status,
                'message' => $payload->message,
            ],
        );
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
