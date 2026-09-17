<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core;

use Quonain\SmartResponse\Contracts\ContentNegotiatorInterface;
use Quonain\SmartResponse\Contracts\CoreResponseFormatterInterface;
use Quonain\SmartResponse\Contracts\SerializerInterface;

/** Framework-independent response orchestration and formatter registry. */
final class ResponseFactory
{
    /** @var array<string, CoreResponseFormatterInterface> */
    private array $formatters = [];

    public function __construct(
        private readonly SerializerInterface $serializer = new NativeSerializer(),
        private readonly ContentNegotiatorInterface $negotiator = new AcceptHeaderNegotiator(),
    ) {
        $this->register('json', new Formatters\JsonFormatter($this->serializer));
        $this->register('xml', new Formatters\XmlFormatter($this->serializer));
        $this->register('text', new Formatters\TextFormatter());
    }

    public function register(string $name, CoreResponseFormatterInterface $formatter): self
    {
        $name = strtolower(trim($name));
        if ($name === '') {
            throw new \InvalidArgumentException('A formatter name is required.');
        }
        $this->formatters[$name] = $formatter;
        return $this;
    }

    /** @param array<string, mixed> $context */
    public function make(mixed $data, string $format = 'json', array $context = []): Response
    {
        $formatter = $this->formatters[strtolower($format)] ?? null;
        if ($formatter === null) {
            throw new \InvalidArgumentException("Unsupported response format [{$format}].");
        }
        return $formatter->format($data, $context);
    }

    /** @param array<string, mixed> $context */
    public function negotiate(mixed $data, string $accept, array $context = []): Response
    {
        $mediaTypes = array_values(array_map(static fn (CoreResponseFormatterInterface $formatter): string => $formatter->mediaType(), $this->formatters));
        $mediaType = $this->negotiator->negotiate($accept, $mediaTypes);
        if ($mediaType === null) {
            throw new \UnexpectedValueException('No registered formatter matches the Accept header.');
        }
        foreach ($this->formatters as $name => $formatter) {
            if ($formatter->mediaType() === $mediaType) {
                return $formatter->format($data, $context);
            }
        }
        throw new \LogicException('The selected formatter is no longer registered.');
    }

    /** @return list<string> */
    public function formats(): array { return array_keys($this->formatters); }
}
