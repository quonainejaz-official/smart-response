<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core;

/** Framework-independent, cache-backed fixed-window rate limiter. */
final class FixedWindowRateLimiter
{
    public function __construct(private readonly CacheStore $cache, private readonly string $prefix = 'smart_response:rate_limit') {}

    /** @return array{allowed: bool, retry_after: int, remaining: int, limit: int} */
    public function attempt(string $key, int $limit = 60, int $decaySeconds = 60): array
    {
        $limit = max(1, $limit);
        $decaySeconds = max(1, $decaySeconds);
        $now = time();
        $bucket = intdiv($now, $decaySeconds);
        $retryAfter = max(1, (($bucket + 1) * $decaySeconds) - $now);
        $cacheKey = $this->prefix.':'.hash('sha256', $bucket.'|'.$key);
        $this->cache->add($cacheKey, 0, $retryAfter);
        $attempts = $this->cache->increment($cacheKey);

        return ['allowed' => $attempts <= $limit, 'retry_after' => $retryAfter, 'remaining' => max(0, $limit - $attempts), 'limit' => $limit];
    }
}
