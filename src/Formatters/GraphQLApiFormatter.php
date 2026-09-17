<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Formatters;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Quonain\SmartResponse\Contracts\ResponseFormatterInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

/** Formats a payload according to the GraphQL response envelope. */
final class GraphQLApiFormatter implements ResponseFormatterInterface
{
    public function format(SmartResponsePayload $payload): BaseResponse
    {
        $body = $payload->success
            ? ['data' => $payload->normalizedData()]
            : ['data' => null, 'errors' => $this->errors($payload)];

        if ($payload->success && $payload->errors !== null) {
            $body['errors'] = $this->errors($payload);
        }

        $response = new JsonResponse($body, $payload->status);
        $response->headers->set('Content-Type', 'application/graphql-response+json');

        foreach ($payload->headers ?? [] as $name => $value) {
            $response->headers->set($name, is_array($value) ? implode(', ', $value) : (string) $value);
        }

        return $response;
    }

    /** @return list<array<string, mixed>> */
    private function errors(SmartResponsePayload $payload): array
    {
        $errors = $payload->errors ?? $payload->message ?? 'Request failed';

        if (! is_array($errors)) {
            return [['message' => (string) $errors]];
        }

        return array_is_list($errors)
            ? array_map(static fn (mixed $error): array => is_array($error) ? $error : ['message' => (string) $error], $errors)
            : [['message' => $payload->message ?? 'Request failed', 'extensions' => ['details' => $errors]]];
    }
}
