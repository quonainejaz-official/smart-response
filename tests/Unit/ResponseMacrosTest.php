<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Quonain\SmartResponse\Tests\TestCase;

uses(TestCase::class);

it('registers response smart macro', function () {
    $request = Request::create('/api/macro', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $this->app->instance('request', $request);

    $response = response()->smart(
        data: ['id' => 1],
        message: 'Macro OK',
    );

    $json = json_decode($response->getContent(), true);

    expect($json['success'])->toBeTrue()
        ->and($json['message'])->toBe('Macro OK')
        ->and($json['data'])->toBe(['id' => 1]);
});

it('supports smart_response helper advanced options', function () {
    $request = Request::create('/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'text/html',
    ]);

    $response = smart_response(
        request: $request,
        data: ['users' => []],
        view: 'users.index',
        message: 'OK',
        headers: ['X-Smart-Response' => '1'],
    );

    expect($response->headers->get('X-Smart-Response'))->toBe('1')
        ->and($response->getStatusCode())->toBe(200);
});
