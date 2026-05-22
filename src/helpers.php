<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;
use Quonain\SmartResponse\Support\RateLimitResponse;

if (! function_exists('smart_response')) {
    /**
     * Build a unified smart response.
     *
     * @param  array<string, mixed>|null  $viewData
     */
    function smart_response(
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
        /** @var SmartResponseManagerInterface $manager */
        $manager = app(SmartResponseManagerInterface::class);

        return $manager->respond(new SmartResponsePayload(
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
        ), $request);
    }
}

if (! function_exists('smart_rate_limit_response')) {
    function smart_rate_limit_response(?string $message = null, ?int $retryAfter = null): Response
    {
        return app(RateLimitResponse::class)->respond($message, $retryAfter);
    }
}

if (! function_exists('smart_created')) {
    function smart_created(mixed $data = null, ?string $message = null, array $meta = []): Response
    {
        return app(SmartResponseManagerInterface::class)->created($data, $message, $meta);
    }
}

if (! function_exists('smart_not_found')) {
    function smart_not_found(?string $message = null, mixed $errors = null): Response
    {
        return app(SmartResponseManagerInterface::class)->notFound($message, $errors);
    }
}
