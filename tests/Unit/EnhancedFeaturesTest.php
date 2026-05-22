<?php

declare(strict_types=1);

use Quonain\SmartResponse\Tests\TestCase;

uses(TestCase::class);

use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;
use Quonain\SmartResponse\Detectors\RequestTypeDetector;

it('detects bearer token requests as api', function () {
    $detector = new RequestTypeDetector(config('smart-response'));

    $request = Request::create('/profile', 'GET', [], [], [], [
        'HTTP_AUTHORIZATION' => 'Bearer test-token',
        'HTTP_ACCEPT' => 'text/html',
    ]);

    expect($detector->expectsApi($request))->toBeTrue();
});

it('enriches api meta with timestamp and request id', function () {
    $request = Request::create('/api/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X-Request-Id' => 'req-123',
    ]);

    $response = app(SmartResponseManagerInterface::class)->respond(
        new \Quonain\SmartResponse\DTO\SmartResponsePayload(
            request: $request,
            data: ['id' => 1],
            message: 'OK',
        ),
    );

    $json = json_decode($response->getContent(), true);
    $meta = (array) $json['meta'];

    expect($meta['request_id'])->toBe('req-123')
        ->and($meta)->toHaveKey('timestamp');
});

it('returns 201 via created helper', function () {
    $request = Request::create('/api/users', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $this->app->instance('request', $request);

    $response = app(SmartResponseManagerInterface::class)->created(['id' => 1], 'Created');

    expect($response->getStatusCode())->toBe(201);
});

it('returns 204 via noContent helper', function () {
    $request = Request::create('/api/users/1', 'DELETE', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $this->app->instance('request', $request);

    $response = app(SmartResponseManagerInterface::class)->noContent();

    expect($response->getStatusCode())->toBe(204)
        ->and($response->getContent())->toBe('');
});

it('returns 404 via notFound helper', function () {
    $request = Request::create('/api/missing', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $this->app->instance('request', $request);

    $response = app(SmartResponseManagerInterface::class)->notFound('User not found');

    $json = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(404)
        ->and($json['success'])->toBeFalse()
        ->and($json['message'])->toBe('User not found');
});

it('adds retry-after header on rate limit response', function () {
    $request = Request::create('/api/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $this->app->instance('request', $request);

    $response = smart_rate_limit_response(null, 120);

    expect($response->getStatusCode())->toBe(429)
        ->and($response->headers->get('Retry-After'))->toBe('120');
});

it('includes cursor pagination meta', function () {
    $request = Request::create('/api/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $items = collect([['id' => 1], ['id' => 2], ['id' => 3]]);
    $paginator = new CursorPaginator($items, 2, null, [
        'path' => 'http://localhost/api/users',
    ]);

    $response = app(SmartResponseManagerInterface::class)->respond(
        new \Quonain\SmartResponse\DTO\SmartResponsePayload(
            request: $request,
            data: $paginator,
            message: 'OK',
        ),
    );

    $json = json_decode($response->getContent(), true);

    expect($json['meta'])->toHaveKeys(['per_page', 'path', 'has_more']);
});
