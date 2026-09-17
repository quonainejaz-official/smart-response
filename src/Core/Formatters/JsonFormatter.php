<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core\Formatters;

use JsonException;
use Quonain\SmartResponse\Contracts\CoreResponseFormatterInterface;
use Quonain\SmartResponse\Contracts\SerializerInterface;
use Quonain\SmartResponse\Core\Response;

final class JsonFormatter implements CoreResponseFormatterInterface
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function mediaType(): string { return 'application/json'; }

    /** @param array<string, mixed> $context */
    public function format(mixed $data, array $context = []): Response
    {
        try {
            $body = json_encode($this->serializer->normalize($data, $context), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('The value cannot be encoded as JSON.', 0, $exception);
        }

        return new Response($body, (int) ($context['status'] ?? 200), $this->headers($context));
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    private function headers(array $context): array
    {
        $rawHeaders = $context['headers'] ?? [];
        $headers = [];
        if (is_array($rawHeaders)) {
            foreach ($rawHeaders as $name => $value) {
                if (is_string($name) && (is_string($value) || is_numeric($value))) {
                    $headers[$name] = (string) $value;
                }
            }
        }

        return ['Content-Type' => $this->mediaType().'; charset=UTF-8', ...$headers];
    }
}
