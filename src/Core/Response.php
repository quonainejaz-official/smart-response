<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core;

/** Framework-independent HTTP response value. */
final class Response
{
    /**
     * @param array<string, mixed>|string|null $body
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly mixed $body,
        private readonly int $status = 200,
        private readonly array $headers = ['Content-Type' => 'application/json; charset=UTF-8'],
    ) {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException('HTTP status must be between 100 and 599.');
        }
    }

    /** @return array<string, mixed>|string|null */
    public function body(): mixed { return $this->body; }
    public function status(): int { return $this->status; }
    /** @return array<string, string> */
    public function headers(): array { return $this->headers; }
    public function json(): string { return json_encode($this->body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES); }
    public function content(): string { return is_string($this->body) ? $this->body : $this->json(); }

    public function withStatus(int $status): self { return new self($this->body, $status, $this->headers); }

    public function withBody(mixed $body): self { return new self($body, $this->status, $this->headers); }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->body, $this->status, [...$this->headers, $name => $value]);
    }

    /** @param array<string, string> $headers */
    public function withHeaders(array $headers): self
    {
        return new self($this->body, $this->status, [...$this->headers, ...$headers]);
    }

    /** @return array{body: array<string, mixed>|string|null, status: int, headers: array<string, string>} */
    public function toArray(): array
    {
        return ['body' => $this->body, 'status' => $this->status, 'headers' => $this->headers];
    }

    /** @param array{body: array<string, mixed>|string|null, status: int, headers: array<string, string>} $value */
    public static function fromArray(array $value): self
    {
        return new self($value['body'], $value['status'], $value['headers']);
    }
}
