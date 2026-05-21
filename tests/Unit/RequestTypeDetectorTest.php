<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Vendor\SmartResponse\Detectors\RequestTypeDetector;

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
