<?php

declare(strict_types=1);

use Quonain\SmartResponse\Runtime\Grpc\GrpcHandler;
use Quonain\SmartResponse\Runtime\Soap\SoapServerAdapter;
use Quonain\SmartResponse\Runtime\WebSocket\WebSocketServer;
use Quonain\SmartResponse\Support\GrpcResponse;

it('normalizes a grpc response through the runtime handler', function (): void {
    $handler = new GrpcHandler(static fn (array $request, array $metadata): GrpcResponse => new GrpcResponse(
        data: ['request' => $request, 'metadata' => $metadata],
        status: 0,
    ));

    expect($handler->handle(['id' => 7], ['authorization' => 'token']))->toBe([
        'success' => true,
        'message' => null,
        'data' => ['request' => ['id' => 7], 'metadata' => ['authorization' => 'token']],
        'meta' => [],
        'errors' => null,
    ]);
});

it('fails clearly when the soap runtime extension is unavailable', function (): void {
    $adapter = new SoapServerAdapter(new class {
        public function ping(): string
        {
            return 'pong';
        }
    });

    if (extension_loaded('soap')) {
        expect($adapter->handle('<?xml version="1.0"?><SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><SOAP-ENV:Body><ping/></SOAP-ENV:Body></SOAP-ENV:Envelope>'))->toBeString();
    } else {
        expect(fn (): string => $adapter->handle())->toThrow(RuntimeException::class, 'SOAP');
    }
});

it('fails clearly when the optional websocket runtime is unavailable', function (): void {
    if (class_exists(\Ratchet\App::class)) {
        expect(true)->toBeTrue();
        return;
    }

    expect(static function (): void {
        (new WebSocketServer('127.0.0.1', 8080, '/', static fn (): array => []))->run();
    })
        ->toThrow(RuntimeException::class, 'cboden/ratchet');
});
