<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Formatters;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Quonain\SmartResponse\Contracts\ResponseFormatterInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

/**
 * Formats responses for APIs that pre-date SmartResponse's envelope.
 *
 * The shape is intentionally configurable because "legacy" APIs rarely
 * share one standard. Defaults preserve the common status/message/data form.
 */
final class LegacyApiFormatter implements ResponseFormatterInterface
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config) {}

    public function format(SmartResponsePayload $payload): BaseResponse
    {
        if ($payload->status === 204) {
            return new JsonResponse(null, 204);
        }

        $keys = $this->config['legacy']['keys'] ?? [];
        $body = [
            $keys['status'] ?? 'status' => $payload->success,
            $keys['message'] ?? 'message' => $payload->message,
            $keys['data'] ?? 'data' => $payload->normalizedData(),
            $keys['errors'] ?? 'errors' => $payload->errors,
        ];

        $response = new JsonResponse($body, $payload->status);

        foreach ($payload->headers ?? [] as $name => $value) {
            $response->headers->set($name, is_array($value) ? implode(', ', $value) : (string) $value);
        }

        return $response;
    }
}
