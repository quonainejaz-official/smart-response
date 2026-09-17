<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Builders;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Quonain\SmartResponse\Contracts\ApiResponseBuilderInterface;
use Quonain\SmartResponse\Contracts\ResponseFormatterInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;
use Quonain\SmartResponse\Formatters\JsonApiFormatter;
use Quonain\SmartResponse\Formatters\XmlApiFormatter;
use Quonain\SmartResponse\Formatters\LegacyApiFormatter;
use Quonain\SmartResponse\Formatters\GraphQLApiFormatter;
use Quonain\SmartResponse\Formatters\SoapApiFormatter;
use Quonain\SmartResponse\Support\ResponseFormatterRegistry;

final class ApiResponseBuilder implements ApiResponseBuilderInterface
{
    public function __construct(
        private readonly ResponseFormatterRegistry $registry,
        /** @var array<string, mixed> */
        private readonly array $config,
    ) {}

    public function success(SmartResponsePayload $payload): JsonResponse|BaseResponse
    {
        return $this->formatter($payload)->format($payload);
    }

    public function error(SmartResponsePayload $payload): JsonResponse|BaseResponse
    {
        return $this->formatter($payload)->format($payload);
    }

    public function validationError(SmartResponsePayload $payload): JsonResponse|BaseResponse
    {
        return $this->formatter($payload)->format($payload);
    }

    private function formatter(SmartResponsePayload $payload): ResponseFormatterInterface
    {
        $format = $payload->format ?? $this->config['default_format'] ?? 'json';

        if ($this->registry->has($format)) {
            return $this->registry->get($format);
        }

        return $this->registry->get('json');
    }
}
