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
        return new self(
            request: $request,
            data: $this->data,
            view: $this->view,
            viewData: $this->viewData,
            message: $this->message,
            success: $this->success,
            errors: $this->errors,
            meta: $this->meta,
            status: $this->status,
            redirect: $this->redirect,
            route: $this->route,
            routeParameters: $this->routeParameters,
            format: $this->format,
            locale: $this->locale,
            flash: $this->flash,
            toast: $this->toast,
            cacheKey: $this->cacheKey,
            cacheTtl: $this->cacheTtl,
            headers: $this->headers,
            inertiaComponent: $this->inertiaComponent,
            useInertia: $this->useInertia,
            useLivewire: $this->useLivewire,
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
