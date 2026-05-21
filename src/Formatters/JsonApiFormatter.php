<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Formatters;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Vendor\SmartResponse\Contracts\ResponseFormatterInterface;
use Vendor\SmartResponse\DTO\SmartResponsePayload;

final class JsonApiFormatter implements ResponseFormatterInterface
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function format(SmartResponsePayload $payload): BaseResponse
    {
        $keys = $this->config['api'];

        $body = [
            $keys['success_key'] => $payload->success,
            $keys['message_key'] => $payload->message,
            $keys['data_key'] => $payload->normalizedData(),
            $keys['meta_key'] => (object) ($payload->meta ?: []),
            $keys['errors_key'] => $payload->errors,
        ];

        $response = new JsonResponse($body, $payload->status);

        if ($payload->headers) {
            $response->withHeaders($payload->headers);
        }

        return $response;
    }
}
