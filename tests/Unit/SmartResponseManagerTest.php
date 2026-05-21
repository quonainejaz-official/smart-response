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
