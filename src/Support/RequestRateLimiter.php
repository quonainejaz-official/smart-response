<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;

/** Fixed-window limiter with no dependency beyond Laravel's cache contract. */
final class RequestRateLimiter
{
    public function __construct(
        private readonly CacheRepository $cache,
        /** @var array<string, mixed> */
        private readonly array $config,
    ) {}

    /** @return array{allowed: bool, retry_after: int, remaining: int, limit: int} */
    public function attempt(Request $request): array
    {
        $limit = max(1, (int) ($this->config['max_attempts'] ?? 60));
        $decay = max(1, (int) ($this->config['decay_seconds'] ?? 60));
        $now = time();
        $bucket = intdiv($now, $decay);
        $retryAfter = max(1, (($bucket + 1) * $decay) - $now);
        $key = $this->key($request, $bucket);

        // add() makes the expiry belong to the first request in this window.
        $this->cache->add($key, 0, $retryAfter);
        $attempts = (int) $this->cache->increment($key);

        return [
            'allowed' => $attempts <= $limit,
            'retry_after' => $retryAfter,
            'remaining' => max(0, $limit - $attempts),
            'limit' => $limit,
        ];
    }

    private function key(Request $request, int $bucket): string
    {
        $prefix = (string) ($this->config['prefix'] ?? 'smart_response:rate_limit');
        $strategy = $this->config['key'] ?? 'user_or_ip';
        $identity = $request->ip() ?: 'unknown';

        if ($strategy === 'user_or_ip' && $request->user() !== null) {
            $identity = 'user:'.(string) $request->user()->getAuthIdentifier();
        }

        if ($strategy === 'route') {
            $identity = (string) ($request->route()->getName() ?? $request->path());
        }

        return $prefix.':'.hash('sha256', $bucket.'|'.$identity.'|'.$request->method().'|'.$request->path());
    }
}
