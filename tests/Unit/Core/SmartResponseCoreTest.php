<?php

declare(strict_types=1);

use Quonain\SmartResponse\Core\ArrayCacheStore;
use Quonain\SmartResponse\Core\FixedWindowRateLimiter;
use Quonain\SmartResponse\Core\SmartResponse;
use Quonain\SmartResponse\Core\Version;

it('exposes one package version for every framework adapter', function () {
    expect(Version::CURRENT)->toBe('1.2.0');
});

it('provides a JSON response without a framework runtime', function () {
    $response = (new SmartResponse())->success(['id' => 1], 'Loaded');

    expect($response->status())->toBe(200)
        ->and($response->headers()['Content-Type'])->toContain('application/json')
        ->and(json_decode($response->json(), true))->toMatchArray(['success' => true, 'data' => ['id' => 1]]);
});

it('provides a cache-backed framework-independent rate limiter', function () {
    $limiter = new FixedWindowRateLimiter(new ArrayCacheStore());

    $first = $limiter->attempt('client:1', 1, 60);
    $second = $limiter->attempt('client:1', 1, 60);

    expect($first['allowed'])->toBeTrue()
        ->and($second['allowed'])->toBeFalse()
        ->and($second['remaining'])->toBe(0)
        ->and($second['retry_after'])->toBeGreaterThan(0);
});

it('provides framework-independent legacy, GraphQL, and XML output', function () {
    $responses = new SmartResponse();

    expect($responses->legacy(['id' => 1])->body())->toHaveKey('status', true)
        ->and($responses->graphQl(['id' => 1])->body())->toBe(['data' => ['id' => 1]])
        ->and($responses->xml(['<unsafe key>' => 'value'])->content())->toContain('<unsafe-key>value</unsafe-key>');
});
