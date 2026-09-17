<?php

declare(strict_types=1);

use Quonain\SmartResponse\Tests\TestCase;

uses(TestCase::class);

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

it('returns json for api requests', function () {
    $request = Request::create('/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $manager = app(SmartResponseManagerInterface::class);

    $response = $manager->respond(new SmartResponsePayload(
        request: $request,
        data: ['id' => 1],
        message: 'OK',
    ));

    $json = json_decode($response->getContent(), true);

    expect($response->headers->get('Content-Type'))->toContain('application/json')
        ->and($json['success'])->toBeTrue()
        ->and($json['data'])->toBe(['id' => 1]);
});

it('returns the opt-in legacy api envelope', function () {
    $request = Request::create('/api/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $response = app(SmartResponseManagerInterface::class)->respond(new SmartResponsePayload(
        request: $request,
        data: ['id' => 1],
        message: 'Loaded',
        format: 'legacy',
    ));

    expect(json_decode($response->getContent(), true))->toMatchArray([
        'status' => true,
        'message' => 'Loaded',
        'data' => ['id' => 1],
        'errors' => null,
    ]);
});

it('returns blade view for web requests', function () {
    $request = Request::create('/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'text/html',
    ]);

    $manager = app(SmartResponseManagerInterface::class);

    $response = $manager->respond(new SmartResponsePayload(
        request: $request,
        data: ['users' => []],
        view: 'users.index',
        message: 'Users loaded',
    ));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toContain('users-index');
});

it('includes pagination meta in api responses', function () {
    $request = Request::create('/api/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $items = collect([['id' => 1], ['id' => 2]]);
    $paginator = new LengthAwarePaginator($items, 2, 10, 1, [
        'path' => 'http://localhost/api/users',
    ]);

    $manager = app(SmartResponseManagerInterface::class);

    $response = $manager->respond(new SmartResponsePayload(
        request: $request,
        data: $paginator,
        message: 'OK',
    ));

    $json = json_decode($response->getContent(), true);

    expect($json['meta'])->toHaveKeys(['current_page', 'per_page', 'total', 'last_page']);
});

it('formats validation errors', function () {
    $request = Request::create('/api/users', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $manager = app(SmartResponseManagerInterface::class);

    $response = $manager->respond(new SmartResponsePayload(
        request: $request,
        message: 'Validation failed',
        success: false,
        errors: [
        'email' => ['The email field is required.'],
    ],
        status: 422,
    ));

    $json = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(422)
        ->and($json['success'])->toBeFalse()
        ->and($json['errors']['email'])->toContain('The email field is required.');
});

it('formats validation errors via helper', function () {
    $request = Request::create('/api/users', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $this->app->instance('request', $request);

    $manager = app(SmartResponseManagerInterface::class);

    $response = $manager->validationError([
        'email' => ['The email field is required.'],
    ]);

    $json = json_decode($response->getContent(), true);

    expect($response->getStatusCode())->toBe(422)
        ->and($json['success'])->toBeFalse()
        ->and($json['errors']['email'])->toContain('The email field is required.');
});

it('success helper returns standardized structure', function () {
    $request = Request::create('/api/test', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $this->app->instance('request', $request);

    $response = app(SmartResponseManagerInterface::class)->success(
        data: ['foo' => 'bar'],
        message: 'Done',
    );

    $json = json_decode($response->getContent(), true);

    expect($json['success'])->toBeTrue()
        ->and($json['message'])->toBe('Done')
        ->and($json['data'])->toBe(['foo' => 'bar']);
});

it('auto-caches get api responses when cache is enabled', function () {
    config()->set('smart-response.cache.enabled', true);
    config()->set('smart-response.cache.store', 'array');
    config()->set('smart-response.meta.include_timestamp', false);
    config()->set('smart-response.meta.include_request_id', false);

    $request = Request::create('/api/cache-test?page=1', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $manager = app(SmartResponseManagerInterface::class);

    $first = $manager->respond(new SmartResponsePayload(
        request: $request,
        data: ['cached' => 1],
        message: 'Cached response',
    ));

    $second = $manager->respond(new SmartResponsePayload(
        request: $request,
        data: ['cached' => 2],
        message: 'New response',
    ));

    $firstJson = json_decode($first->getContent(), true);
    $secondJson = json_decode($second->getContent(), true);

    expect($firstJson['data'])->toBe(['cached' => 1])
        ->and($secondJson['data'])->toBe(['cached' => 1]);
});

it('does not cache authenticated API responses unless explicitly enabled', function () {
    config()->set('smart-response.cache.enabled', true);
    config()->set('smart-response.cache.store', 'array');

    $request = Request::create('/api/private', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(fn () => new class {
        public function getAuthIdentifier(): int { return 7; }
    });
    $manager = app(SmartResponseManagerInterface::class);

    $first = $manager->respond(new SmartResponsePayload(request: $request, data: ['version' => 1]));
    $second = $manager->respond(new SmartResponsePayload(request: $request, data: ['version' => 2]));

    expect(json_decode($first->getContent(), true)['data'])->toBe(['version' => 1])
        ->and(json_decode($second->getContent(), true)['data'])->toBe(['version' => 2]);
});

it('requires an explicit key before caching an authenticated API response', function () {
    config()->set('smart-response.cache.enabled', true);
    config()->set('smart-response.cache.store', 'array');
    config()->set('smart-response.cache.cache_authenticated', true);
    config()->set('smart-response.meta.include_timestamp', false);
    config()->set('smart-response.meta.include_request_id', false);

    $request = Request::create('/api/private-keyed', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $request->setUserResolver(fn () => new class {
        public function getAuthIdentifier(): int { return 7; }
    });
    $manager = app(SmartResponseManagerInterface::class);

    $first = $manager->respond(new SmartResponsePayload(request: $request, data: ['version' => 1], cacheKey: 'user:7:private-keyed'));
    $second = $manager->respond(new SmartResponsePayload(request: $request, data: ['version' => 2], cacheKey: 'user:7:private-keyed'));

    expect(json_decode($first->getContent(), true)['data'])->toBe(['version' => 1])
        ->and(json_decode($second->getContent(), true)['data'])->toBe(['version' => 1]);
});

it('does not cache bearer-token API responses without an explicit isolated key', function () {
    config()->set('smart-response.cache.enabled', true);
    config()->set('smart-response.cache.store', 'array');
    config()->set('smart-response.meta.include_timestamp', false);
    config()->set('smart-response.meta.include_request_id', false);

    $request = Request::create('/api/token-private', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_AUTHORIZATION' => 'Bearer private-token',
    ]);
    $manager = app(SmartResponseManagerInterface::class);

    $first = $manager->respond(new SmartResponsePayload(request: $request, data: ['version' => 1]));
    $second = $manager->respond(new SmartResponsePayload(request: $request, data: ['version' => 2]));

    expect(json_decode($first->getContent(), true)['data'])->toBe(['version' => 1])
        ->and(json_decode($second->getContent(), true)['data'])->toBe(['version' => 2]);
});

it('does not cache responses with dynamic request metadata by default', function () {
    config()->set('smart-response.cache.enabled', true);
    config()->set('smart-response.cache.store', 'array');

    $request = Request::create('/api/dynamic-meta', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json']);
    $manager = app(SmartResponseManagerInterface::class);

    $first = $manager->respond(new SmartResponsePayload(request: $request, data: ['version' => 1]));
    $second = $manager->respond(new SmartResponsePayload(request: $request, data: ['version' => 2]));

    expect(json_decode($first->getContent(), true)['data'])->toBe(['version' => 1])
        ->and(json_decode($second->getContent(), true)['data'])->toBe(['version' => 2]);
});

it('supports the fluent builder and named response profiles', function () {
    $request = Request::create('/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $response = app(SmartResponseManagerInterface::class)
        ->make(['id' => 1])
        ->request($request)
        ->profile('legacy-v1')
        ->message('Loaded')
        ->send();

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode($response->getContent(), true))->toHaveKeys(['status', 'message', 'data', 'errors']);
});
