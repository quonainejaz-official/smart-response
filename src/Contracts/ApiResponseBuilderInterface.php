<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

interface ApiResponseBuilderInterface
{
    public function success(SmartResponsePayload $payload): JsonResponse|Response;

    public function error(SmartResponsePayload $payload): JsonResponse|Response;

    public function validationError(SmartResponsePayload $payload): JsonResponse|Response;
}
