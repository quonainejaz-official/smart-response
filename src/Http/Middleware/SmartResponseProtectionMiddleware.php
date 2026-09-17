<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;
use Quonain\SmartResponse\Support\RequestRateLimiter;
use Symfony\Component\HttpFoundation\Response;

/** Applies opt-in request limits, rate limits, and conservative response headers. */
final class SmartResponseProtectionMiddleware
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly SmartResponseManagerInterface $manager,
        private readonly ?RequestRateLimiter $rateLimiter,
        private readonly array $config,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('smart_response', true);

        if ($this->exceedsPayloadLimit($request)) {
            return $this->error($request, 'Request payload is too large.', (int) ($this->config['payload_limits']['status'] ?? 413));
        }

        if (($this->config['rate_limit']['enabled'] ?? false) && $this->rateLimiter !== null) {
            $result = $this->rateLimiter->attempt($request);

            if (! $result['allowed']) {
                $response = $this->error($request, (string) ($this->config['rate_limit']['message'] ?? 'Too many requests. Please try again later.'), 429);
                $response->headers->set('Retry-After', (string) $result['retry_after']);
                $response->headers->set('X-RateLimit-Limit', (string) $result['limit']);
                $response->headers->set('X-RateLimit-Remaining', '0');

                return $response;
            }
        }

        $response = $next($request);
        $this->applySecurityHeaders($response);

        return $response;
    }

    private function exceedsPayloadLimit(Request $request): bool
    {
        $limits = $this->config['payload_limits'] ?? [];
        if (! ($limits['enabled'] ?? false)) {
            return false;
        }

        $maximum = max(1, (int) ($limits['max_bytes'] ?? 1048576));
        $length = $request->headers->get('Content-Length');

        return $length !== null && ctype_digit($length) && (int) $length > $maximum;
    }

    private function error(Request $request, string $message, int $status): Response
    {
        return $this->manager->respond(new SmartResponsePayload(
            request: $request,
            message: $message,
            success: false,
            status: $status,
        ));
    }

    private function applySecurityHeaders(Response $response): void
    {
        $headers = $this->config['security_headers'] ?? [];
        if (! ($headers['enabled'] ?? false)) {
            return;
        }

        foreach (($headers['headers'] ?? []) as $name => $value) {
            if (is_string($name) && is_string($value) && ! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }
    }
}
