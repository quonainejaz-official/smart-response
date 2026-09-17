<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core;

/** Minimal cache contract that can be adapted to any PHP framework or PSR-16 store. */
interface CacheStore
{
    public function get(string $key): mixed;
    public function put(string $key, mixed $value, int $ttlSeconds): void;
    public function add(string $key, mixed $value, int $ttlSeconds): bool;
    public function increment(string $key): int;
}
