<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Contracts;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

interface SmartResponseManagerInterface
{
    public function respond(SmartResponsePayload $payload, ?Request $request = null): Response;

    public function success(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
    ): Response;

    public function error(
        ?string $message = null,
        mixed $errors = null,
        int $status = 400,
        array $meta = [],
    ): Response;

    public function validationError(
        mixed $errors,
        ?string $message = null,
        int $status = 422,
    ): Response;

    public function created(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
    ): Response;

    public function noContent(): Response;

    public function notFound(?string $message = null, mixed $errors = null): Response;

    public function unauthorized(?string $message = null, mixed $errors = null): Response;

    public function forbidden(?string $message = null, mixed $errors = null): Response;
}
