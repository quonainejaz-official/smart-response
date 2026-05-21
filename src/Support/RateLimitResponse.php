<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Support;

use Symfony\Component\HttpFoundation\Response;
use Vendor\SmartResponse\Contracts\SmartResponseManagerInterface;

final class RateLimitResponse
{
    public function __construct(
        private readonly SmartResponseManagerInterface $manager,
        private readonly array $config,
    ) {}

    public function respond(?string $message = null): Response
    {
        $rateLimit = $this->config['rate_limit'];

        return $this->manager->error(
            $message ?? ($rateLimit['message'] ?? 'Too many requests'),
            null,
            (int) ($rateLimit['status'] ?? 429),
        );
    }
}
