<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Vendor\SmartResponse\DTO\SmartResponsePayload;

interface ApiResponseBuilderInterface
{
    public function success(SmartResponsePayload $payload): JsonResponse|Response;

    public function error(SmartResponsePayload $payload): JsonResponse|Response;

    public function validationError(SmartResponsePayload $payload): JsonResponse|Response;
}
