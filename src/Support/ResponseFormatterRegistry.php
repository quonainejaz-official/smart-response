<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

use InvalidArgumentException;
use Quonain\SmartResponse\Contracts\ResponseFormatterInterface;

/** Registry for built-in and application-defined response formats. */
final class ResponseFormatterRegistry
{
    /** @var array<string, ResponseFormatterInterface> */
    private array $formatters = [];

    public function register(string $format, ResponseFormatterInterface $formatter): void
    {
        $normalized = strtolower(trim($format));

        if ($normalized === '') {
            throw new InvalidArgumentException('A response format name is required.');
        }

        $this->formatters[$normalized] = $formatter;
    }

    public function has(string $format): bool
    {
        return isset($this->formatters[strtolower($format)]);
    }

    public function get(string $format): ResponseFormatterInterface
    {
        $normalized = strtolower($format);

        if (! isset($this->formatters[$normalized])) {
            throw new InvalidArgumentException("Unsupported response format [{$format}].");
        }

        return $this->formatters[$normalized];
    }

    /** @return list<string> */
    public function formats(): array
    {
        return array_keys($this->formatters);
    }
}
