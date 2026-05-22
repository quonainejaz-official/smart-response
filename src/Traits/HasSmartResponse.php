<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Traits;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

trait HasSmartResponse
{
    /**
     * Unified smart response — auto-detects API vs Web.
     *
     * @param  array<string, mixed>|null  $viewData
     * @param  array<string, mixed>  $meta
     */
    protected function smartResponse(
        ?Request $request = null,
        mixed $data = null,
        ?string $view = null,
        ?array $viewData = null,
        ?string $message = null,
        bool $success = true,
        mixed $errors = null,
        array $meta = [],
        int $status = 200,
        ?string $redirect = null,
        ?string $route = null,
        array $routeParameters = [],
        ?string $format = null,
        bool $flash = true,
        bool $toast = false,
        ?string $cacheKey = null,
        ?int $cacheTtl = null,
        ?array $headers = null,
        ?string $inertiaComponent = null,
        bool $useInertia = false,
        bool $useLivewire = false,
    ): Response {
        $payload = new SmartResponsePayload(
            request: $request ?? request(),
            data: $data,
            view: $view,
            viewData: $viewData,
            message: $message,
            success: $success,
            errors: $errors,
            meta: $meta,
            status: $status,
            redirect: $redirect,
            route: $route,
            routeParameters: $routeParameters,
            format: $format,
            flash: $flash,
            toast: $toast,
            cacheKey: $cacheKey,
            cacheTtl: $cacheTtl,
            headers: $headers,
            inertiaComponent: $inertiaComponent,
            useInertia: $useInertia,
            useLivewire: $useLivewire,
        );

        return $this->smartResponseManager()->respond($payload, $request);
    }

    protected function smartSuccess(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
    ): Response {
        return $this->smartResponseManager()->success($data, $message, $meta, $status);
    }

    protected function smartError(
        ?string $message = null,
        mixed $errors = null,
        int $status = 400,
        array $meta = [],
    ): Response {
        return $this->smartResponseManager()->error($message, $errors, $status, $meta);
    }

    protected function smartValidationError(
        mixed $errors,
        ?string $message = null,
        int $status = 422,
    ): Response {
        return $this->smartResponseManager()->validationError($errors, $message, $status);
    }

    protected function smartCreated(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
    ): Response {
        return $this->smartResponseManager()->created($data, $message, $meta);
    }

    protected function smartNoContent(): Response
    {
        return $this->smartResponseManager()->noContent();
    }

    protected function smartNotFound(?string $message = null, mixed $errors = null): Response
    {
        return $this->smartResponseManager()->notFound($message, $errors);
    }

    protected function smartUnauthorized(?string $message = null, mixed $errors = null): Response
    {
        return $this->smartResponseManager()->unauthorized($message, $errors);
    }

    protected function smartForbidden(?string $message = null, mixed $errors = null): Response
    {
        return $this->smartResponseManager()->forbidden($message, $errors);
    }

    protected function smartResponseManager(): SmartResponseManagerInterface
    {
        return app(SmartResponseManagerInterface::class);
    }
}
