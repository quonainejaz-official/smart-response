<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core;

/** Pure-PHP response factory for any framework, router, or standalone endpoint. */
final class SmartResponse
{
    /**
     * @param array<string, mixed> $meta
     * @param array<string, string> $headers
     */
    public function success(mixed $data = null, ?string $message = null, array $meta = [], int $status = 200, array $headers = []): Response
    {
        return new Response(['success' => true, 'message' => $message, 'data' => $data, 'meta' => (object) $meta, 'errors' => null], $status, $this->headers($headers));
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, string> $headers
     */
    public function error(?string $message = null, mixed $errors = null, int $status = 400, array $meta = [], array $headers = []): Response
    {
        return new Response(['success' => false, 'message' => $message, 'data' => null, 'meta' => (object) $meta, 'errors' => $errors], $status, $this->headers($headers));
    }

    /** @param array<string, string> $headers */
    public function rateLimited(?string $message = null, int $retryAfter = 60, array $headers = []): Response
    {
        return $this->error($message ?? 'Too many requests. Please try again later.', null, 429, [], ['Retry-After' => (string) max(1, $retryAfter), ...$headers]);
    }

    /**
     * @param array<string, string> $keys
     * @param array<string, string> $headers
     */
    public function legacy(mixed $data = null, ?string $message = null, bool $success = true, mixed $errors = null, int $status = 200, array $keys = [], array $headers = []): Response
    {
        return new Response([
            $keys['status'] ?? 'status' => $success,
            $keys['message'] ?? 'message' => $message,
            $keys['data'] ?? 'data' => $data,
            $keys['errors'] ?? 'errors' => $errors,
        ], $status, $this->headers($headers));
    }

    /** @param array<string, string> $headers */
    public function graphQl(mixed $data = null, mixed $errors = null, int $status = 200, array $headers = []): Response
    {
        $body = ['data' => $data];
        if ($errors !== null) {
            $body['errors'] = is_array($errors) && array_is_list($errors) ? $errors : [['message' => (string) $errors]];
        }

        return new Response($body, $status, ['Content-Type' => 'application/graphql-response+json; charset=UTF-8', ...$headers]);
    }

    /**
     * @param array<string, mixed> $meta
     * @param array<string, string> $headers
     */
    public function xml(mixed $data = null, ?string $message = null, array $meta = [], bool $success = true, mixed $errors = null, int $status = 200, array $headers = []): Response
    {
        $body = ['success' => $success, 'message' => $message, 'data' => $data, 'meta' => $meta, 'errors' => $errors];

        return new Response('<?xml version="1.0" encoding="UTF-8"?><response>'.$this->xmlFragment($body).'</response>', $status, ['Content-Type' => 'application/xml; charset=UTF-8', ...$headers]);
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function headers(array $headers): array
    {
        return ['Content-Type' => 'application/json; charset=UTF-8', ...$headers];
    }

    private function xmlFragment(mixed $value, string $name = 'item'): string
    {
        $name = $this->xmlName($name);
        if (! is_iterable($value)) {
            return '<'.$name.'>'.htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8').'</'.$name.'>';
        }

        $children = '';
        foreach ($value as $key => $item) {
            $children .= $this->xmlFragment($item, is_int($key) ? 'item' : (string) $key);
        }

        return '<'.$name.'>'.$children.'</'.$name.'>';
    }

    private function xmlName(string $name): string
    {
        $name = trim(preg_replace('/[^A-Za-z0-9_.-]/', '-', $name) ?? '', '.-');

        return $name !== '' && (ctype_alpha($name[0]) || $name[0] === '_') ? $name : 'item';
    }
}
