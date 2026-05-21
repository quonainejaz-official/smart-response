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
        ), $request);
    }
}

if (! function_exists('smart_rate_limit_response')) {
    function smart_rate_limit_response(?string $message = null): Response
    {
        return app(RateLimitResponse::class)->respond($message);
    }
}
