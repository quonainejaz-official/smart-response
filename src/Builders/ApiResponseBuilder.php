<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Builders;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Vendor\SmartResponse\Contracts\ApiResponseBuilderInterface;
use Vendor\SmartResponse\Contracts\ResponseFormatterInterface;
use Vendor\SmartResponse\DTO\SmartResponsePayload;
use Vendor\SmartResponse\Formatters\JsonApiFormatter;
use Vendor\SmartResponse\Formatters\XmlApiFormatter;

final class ApiResponseBuilder implements ApiResponseBuilderInterface
{
    public function __construct(
        private readonly JsonApiFormatter $jsonFormatter,
        private readonly XmlApiFormatter $xmlFormatter,
        private readonly array $config,
    ) {}

    public function success(SmartResponsePayload $payload): JsonResponse|Response
    {
        return $this->formatter($payload)->format($payload);
    }

    public function error(SmartResponsePayload $payload): JsonResponse|Response
    {
        return $this->formatter($payload)->format($payload);
    }

    public function validationError(SmartResponsePayload $payload): JsonResponse|Response
    {
        return $this->formatter($payload)->format($payload);
    }

    private function formatter(SmartResponsePayload $payload): ResponseFormatterInterface
    {
        $format = $payload->format ?? $this->config['default_format'] ?? 'json';

        return match ($format) {
            'xml' => $this->xmlFormatter,
            default => $this->jsonFormatter,
        };
    }
}
