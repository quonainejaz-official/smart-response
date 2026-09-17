<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Quonain\SmartResponse\Detectors\RequestTypeDetector;

beforeEach(function () {
    $this->detector = new RequestTypeDetector(
        require dirname(__DIR__, 2).'/config/smart-response.php'
    );
});

it('detects json requests via Accept header', function () {
    $request = Request::create('/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    expect($this->detector->expectsJson($request))->toBeTrue()
        ->and($this->detector->expectsApi($request))->toBeTrue()
        ->and($this->detector->expectsWeb($request))->toBeFalse();
});

it('detects web requests without json accept', function () {
    $request = Request::create('/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'text/html,application/xhtml+xml',
    ]);

    expect($this->detector->expectsJson($request))->toBeFalse()
        ->and($this->detector->expectsWeb($request))->toBeTrue();
});

it('detects xml format preference', function () {
    $request = Request::create('/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/xml',
    ]);

    expect($this->detector->expectsXml($request))->toBeTrue()
        ->and($this->detector->getPreferredFormat($request))->toBe('xml');
});

it('detects api routes by prefix', function () {
    $request = Request::create('/api/users', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'text/html',
    ]);

    expect($this->detector->expectsJson($request))->toBeTrue();
});

it('prioritizes the explicit format header', function () {
    $request = Request::create('/users.xml?format=json', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/xml',
        'HTTP_X_SMART_RESPONSE_FORMAT' => 'legacy',
    ]);

    expect($this->detector->getPreferredFormat($request))->toBe('legacy');
});

it('supports an explicit query format', function () {
    $request = Request::create('/users', 'GET', ['format' => 'soap'], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    expect($this->detector->getPreferredFormat($request))->toBe('soap');
});

it('supports protocol route suffixes', function () {
    $request = Request::create('/users.graphql', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    expect($this->detector->getPreferredFormat($request))->toBe('graphql');
});

it('treats an explicit soap suffix as an api request', function () {
    $request = Request::create('/users.soap', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'text/html',
    ]);

    expect($this->detector->expectsApi($request))->toBeTrue()
        ->and($this->detector->getPreferredFormat($request))->toBe('soap');
});

it('ignores unsupported explicit formats', function () {
    $request = Request::create('/users?format=unknown', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    expect($this->detector->getPreferredFormat($request))->toBe('json');
});
