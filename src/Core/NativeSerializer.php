<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core;

use JsonSerializable;
use Quonain\SmartResponse\Contracts\SerializerInterface;
use Stringable;
use Traversable;

/** Conservative default normalizer for values commonly returned by PHP APIs. */
final class NativeSerializer implements SerializerInterface
{
    /** @param array<string, mixed> $context */
    public function normalize(mixed $value, array $context = []): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof JsonSerializable) {
            return $this->normalize($value->jsonSerialize(), $context);
        }

        if ($value instanceof Traversable) {
            return $this->normalize(iterator_to_array($value), $context);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->normalize($item, $context);
            }

            return $result;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_object($value)) {
            return $this->normalize(get_object_vars($value), $context);
        }

        throw new \InvalidArgumentException('The value cannot be normalized by the native serializer.');
    }
}
