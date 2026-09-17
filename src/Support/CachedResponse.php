<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

use Symfony\Component\HttpFoundation\Response;

/**
 * A scalar-only cache representation. Storing an HTTP Response instance can
 * retain framework state and is not supported by all cache drivers.
 */
final class CachedResponse
{
    /** @param array<string, list<string>> $headers */
    public function __construct(
        private readonly string $content,
        private readonly int $status,
        private readonly array $headers,
    ) {}

    public static function fromResponse(Response $response): self
    {
        /** @var array<string, list<string>> $headers */
        $headers = $response->headers->all();
        unset($headers['set-cookie'], $headers['Set-Cookie']);

        return new self($response->getContent() ?: '', $response->getStatusCode(), $headers);
    }

    public function toResponse(): Response
    {
        return new Response($this->content, $this->status, $this->headers);
    }
}
