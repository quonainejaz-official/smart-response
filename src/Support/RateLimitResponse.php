<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

use Symfony\Component\HttpFoundation\Response;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;

final class RateLimitResponse
{
    public function __construct(
        private readonly SmartResponseManagerInterface $manager,
        private readonly array $config,
    ) {}

    public function respond(?string $message = null, ?int $retryAfter = null): Response
    {
        $rateLimit = $this->config['rate_limit'];

        $response = $this->manager->error(
            $message ?? ($rateLimit['message'] ?? 'Too many requests'),
            null,
            (int) ($rateLimit['status'] ?? 429),
        );

        $seconds = $retryAfter ?? ($rateLimit['retry_after_seconds'] ?? null);

        if ($seconds !== null) {
            $response->headers->set('Retry-After', (string) $seconds);
        }

        return $response;
    }
}
