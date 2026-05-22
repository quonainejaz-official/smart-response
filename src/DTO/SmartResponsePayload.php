<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\DTO;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Immutable payload describing a smart response request.
 */
final class SmartResponsePayload
{
    /**
     * @param  array<string, mixed>|null  $viewData
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>|null  $headers
     */
    public function __construct(
        public readonly ?Request $request = null,
        public readonly mixed $data = null,
        public readonly ?string $view = null,
        public readonly ?array $viewData = null,
        public readonly ?string $message = null,
        public readonly bool $success = true,
        public readonly mixed $errors = null,
        public readonly array $meta = [],
        public readonly int $status = 200,
        public readonly ?string $redirect = null,
        public readonly ?string $route = null,
        public readonly array $routeParameters = [],
        public readonly ?string $format = null,
        public readonly ?string $locale = null,
        public readonly bool $flash = true,
        public readonly bool $toast = false,
        public readonly ?string $cacheKey = null,
        public readonly ?int $cacheTtl = null,
        public readonly ?array $headers = null,
        public readonly ?string $inertiaComponent = null,
        public readonly bool $useInertia = false,
        public readonly bool $useLivewire = false,
    ) {}

    public function withRequest(Request $request): self
    {
        return $this->replicate(request: $request);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function withMeta(array $meta): self
    {
        return $this->replicate(meta: array_merge($this->meta, $meta));
    }

    public function replicate(
        ?Request $request = null,
        mixed $data = null,
        ?string $view = null,
        ?array $viewData = null,
        ?string $message = null,
        ?bool $success = null,
        mixed $errors = null,
        ?array $meta = null,
        ?int $status = null,
        ?string $redirect = null,
        ?string $route = null,
        ?array $routeParameters = null,
        ?string $format = null,
        ?string $locale = null,
        ?bool $flash = null,
        ?bool $toast = null,
        ?string $cacheKey = null,
        ?int $cacheTtl = null,
        ?array $headers = null,
        ?string $inertiaComponent = null,
        ?bool $useInertia = null,
        ?bool $useLivewire = null,
    ): self {
        return new self(
            request: $request ?? $this->request,
            data: $data ?? $this->data,
            view: $view ?? $this->view,
            viewData: $viewData ?? $this->viewData,
            message: $message ?? $this->message,
            success: $success ?? $this->success,
            errors: $errors ?? $this->errors,
            meta: $meta ?? $this->meta,
            status: $status ?? $this->status,
            redirect: $redirect ?? $this->redirect,
            route: $route ?? $this->route,
            routeParameters: $routeParameters ?? $this->routeParameters,
            format: $format ?? $this->format,
            locale: $locale ?? $this->locale,
            flash: $flash ?? $this->flash,
            toast: $toast ?? $this->toast,
            cacheKey: $cacheKey ?? $this->cacheKey,
            cacheTtl: $cacheTtl ?? $this->cacheTtl,
            headers: $headers ?? $this->headers,
            inertiaComponent: $inertiaComponent ?? $this->inertiaComponent,
            useInertia: $useInertia ?? $this->useInertia,
            useLivewire: $useLivewire ?? $this->useLivewire,
        );
    }

    public function normalizedData(): mixed
    {
        $data = $this->data;

        if ($data instanceof JsonResource) {
            return $data->resolve();
        }

        if ($data instanceof Collection) {
            return $data->values()->all();
        }

        return $data;
    }
}
