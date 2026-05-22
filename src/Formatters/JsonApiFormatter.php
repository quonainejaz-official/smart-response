<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Formatters;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Quonain\SmartResponse\Contracts\ResponseFormatterInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

final class JsonApiFormatter implements ResponseFormatterInterface
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function format(SmartResponsePayload $payload): BaseResponse
    {
        if ($payload->status === 204) {
            $response = new JsonResponse(null, 204);
        } else {
            $keys = $this->config['api'];

            $body = [
                $keys['success_key'] => $payload->success,
                $keys['message_key'] => $payload->message,
                $keys['data_key'] => $payload->normalizedData(),
                $keys['meta_key'] => (object) ($payload->meta ?: []),
                $keys['errors_key'] => $payload->errors,
            ];

            $response = new JsonResponse($body, $payload->status);
        }

        if ($payload->headers) {
            foreach ($payload->headers as $name => $value) {
                $response->headers->set($name, is_array($value) ? implode(', ', $value) : (string) $value);
            }
        }

        return $response;
    }
}
