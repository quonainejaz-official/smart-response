<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Http\Client;

use Quonain\SmartResponse\Exceptions\OutboundRequestException;

final class HttpResponse
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = [],
        public readonly float $duration = 0.0,
        public readonly int $retryCount = 0,
    ) {}

    public function header(string $name, ?string $default = null): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp($key, $name) === 0) return $value;
        }
        return $default;
    }

    public function json(bool $associative = true): mixed
    {
        return json_decode($this->body, $associative, 512, JSON_THROW_ON_ERROR);
    }

    public function decoded(): mixed
    {
        $type = strtolower((string) $this->header('Content-Type', ''));
        if (str_contains($type, 'json')) {
            try { return $this->json(); } catch (\JsonException) { /* return raw body */ }
        }
        if (str_contains($type, 'xml') && function_exists('simplexml_load_string')) {
            $xml = @simplexml_load_string($this->body, \SimpleXMLElement::class, LIBXML_NONET);
            if ($xml !== false) return $xml;
        }
        return $this->body;
    }

    public function successful(): bool { return $this->status >= 200 && $this->status < 300; }
    public function failed(): bool { return ! $this->successful(); }

    public function throw(): self
    {
        if ($this->failed()) throw new OutboundRequestException('External API returned HTTP '.$this->status.'.', 'external_api_http_error', $this->status);
        return $this;
    }

    public function map(string|callable $mapper): mixed
    {
        $value = $this->decoded();
        if (is_string($mapper) && class_exists($mapper)) return $mapper::fromArray(is_array($value) ? $value : ['value' => $value]);
        return $mapper($value, $this);
    }
}
