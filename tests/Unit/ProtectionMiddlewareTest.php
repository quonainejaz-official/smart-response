<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Quonain\SmartResponse\Http\Middleware\SmartResponseProtectionMiddleware;
use Quonain\SmartResponse\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

uses(TestCase::class);

it('adds configured defensive headers without replacing application headers', function () {
    config()->set('smart-response.security_headers.enabled', true);

    $request = Request::create('/api/headers', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $response = app(SmartResponseProtectionMiddleware::class)->handle(
        $request,
        fn (): Response => new Response('ok', 200, ['X-Frame-Options' => 'DENY']),
    );

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($response->headers->get('X-Frame-Options'))->toBe('DENY');
});

it('rejects an oversized declared request before the next middleware runs', function () {
    config()->set('smart-response.payload_limits.enabled', true);
    config()->set('smart-response.payload_limits.max_bytes', 8);

    $request = Request::create('/api/upload', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_LENGTH' => '9',
    ]);

    $response = app(SmartResponseProtectionMiddleware::class)->handle(
        $request,
        fn (): Response => throw new RuntimeException('The next middleware must not run.'),
    );

    expect($response->getStatusCode())->toBe(413)
        ->and(json_decode($response->getContent(), true)['success'])->toBeFalse();
});

it('limits requests and returns retry metadata', function () {
    config()->set('smart-response.rate_limit.enabled', true);
    config()->set('smart-response.rate_limit.max_attempts', 1);
    config()->set('smart-response.rate_limit.decay_seconds', 60);
    config()->set('smart-response.rate_limit.store', 'array');

    $request = Request::create('/api/limited', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'REMOTE_ADDR' => '203.0.113.10',
    ]);
    $middleware = app(SmartResponseProtectionMiddleware::class);

    $first = $middleware->handle($request, fn (): Response => new Response('ok'));
    $second = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($first->getStatusCode())->toBe(200)
        ->and($second->getStatusCode())->toBe(429)
        ->and($second->headers->get('Retry-After'))->not->toBeNull()
        ->and($second->headers->get('X-RateLimit-Remaining'))->toBe('0');
});
