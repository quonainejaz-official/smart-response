<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core;

/** Process-local cache suitable for tests, CLI programs, and small PHP apps. */
final class ArrayCacheStore implements CacheStore
{
    /** @var array<string, array{value: mixed, expires_at: int}> */
    private array $items = [];

    public function get(string $key): mixed
    {
        $item = $this->items[$key] ?? null;
        if ($item === null || $item['expires_at'] <= time()) {
            unset($this->items[$key]);

            return null;
        }

        return $item['value'];
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->items[$key] = ['value' => $value, 'expires_at' => time() + max(1, $ttlSeconds)];
    }

    public function add(string $key, mixed $value, int $ttlSeconds): bool
    {
        if ($this->get($key) !== null) {
            return false;
        }

        $this->put($key, $value, $ttlSeconds);

        return true;
    }

    public function increment(string $key): int
    {
        $item = $this->items[$key] ?? null;
        if ($item === null || $item['expires_at'] <= time()) {
            $this->put($key, 1, 1);

            return 1;
        }

        $value = (int) $item['value'] + 1;
        $this->items[$key]['value'] = $value;

        return $value;
    }
}
