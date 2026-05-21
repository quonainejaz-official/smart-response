<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Vendor\SmartResponse\Builders\ApiResponseBuilder;
use Vendor\SmartResponse\Builders\WebResponseBuilder;
use Vendor\SmartResponse\Contracts\RequestDetectorInterface;
use Vendor\SmartResponse\Contracts\SmartResponseManagerInterface;
use Vendor\SmartResponse\DTO\SmartResponsePayload;
use Vendor\SmartResponse\Events\SmartResponsePrepared;
use Vendor\SmartResponse\Events\SmartResponsePreparing;
use Vendor\SmartResponse\Support\MessageTranslator;
use Vendor\SmartResponse\Support\PaginationTransformer;
use Vendor\SmartResponse\Support\ValidationErrorFormatter;

final class SmartResponseManager implements SmartResponseManagerInterface
{
    public function __construct(
        private readonly RequestDetectorInterface $detector,
        private readonly ApiResponseBuilder $apiBuilder,
        private readonly WebResponseBuilder $webBuilder,
        private readonly PaginationTransformer $pagination,
        private readonly ValidationErrorFormatter $validationFormatter,
        private readonly MessageTranslator $translator,
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

        return new SmartResponsePayload(
            request: $payload->request,
            data: $transformed['data'],
            view: $payload->view,
            viewData: $payload->viewData,
            message: $payload->message,
            success: $payload->success,
            errors: $payload->errors,
            meta: array_merge($payload->meta, $transformed['meta']),
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
        return ($this->config['cache']['enabled'] ?? false)
            && $payload->cacheKey !== null
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
        if ($payload->cacheKey === null) {
            return null;
        }

        $prefix = $this->config['cache']['prefix'] ?? 'smart_response';

        return "{$prefix}:{$payload->cacheKey}";
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
