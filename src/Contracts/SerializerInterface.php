<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Contracts;

/** Normalizes application values without coupling the core to a serializer vendor. */
interface SerializerInterface
{
    /** @param array<string, mixed> $context */
    public function normalize(mixed $value, array $context = []): mixed;
}
