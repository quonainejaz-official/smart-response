<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Http\Client;

use Quonain\SmartResponse\Exceptions\OutboundRequestException;

final class OutboundClient
{
    /** @param array<string, array<string, mixed>> $providers
     * @param array<string, mixed> $defaults
     */
    public function __construct(private readonly array $providers = [], private readonly array $defaults = [], private readonly ?\Closure $logger = null) {}

    public function request(string $provider): OutboundRequest
    {
        $config = $this->providers[$provider] ?? null;
        if (! is_array($config)) throw new OutboundRequestException("Unknown API provider: {$provider}", 'unknown_api_provider', null, $provider);
        return new OutboundRequest($provider, array_merge($this->defaults, $config), $this->logger);
    }
}
