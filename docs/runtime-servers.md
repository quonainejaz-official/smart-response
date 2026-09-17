# Runtime servers

SmartResponse keeps controller response logic independent from the transport. These runtime entrypoints connect that logic to real protocol servers.

## SOAP

Enable PHP's SOAP extension and expose a service object:

```php
use Quonain\SmartResponse\Runtime\Soap\SoapServerAdapter;

$server = new SoapServerAdapter(new OrderService(), public_path('soap.wsdl'));
$xml = $server->handle(file_get_contents('php://input') ?: null);

return response($xml, 200)->header('Content-Type', 'text/xml; charset=utf-8');
```

`SoapServerAdapter` uses PHP's native `SoapServer`, so WSDL dispatch and SOAP envelopes are handled by the PHP runtime.

## WebSocket

Install a Ratchet version compatible with the application's Laravel and Symfony versions, then run the component:

```php
use Quonain\SmartResponse\Runtime\WebSocket\WebSocketServer;
use Quonain\SmartResponse\Support\WebSocketMessage;

(new WebSocketServer('127.0.0.1', 8080, '/smart-response',
    static fn (array $request): WebSocketMessage => new WebSocketMessage(
        data: app(OrderController::class)->handle($request),
        event: 'order.updated',
    ),
))->run();
```

The component accepts JSON messages and sends normalized SmartResponse envelopes. Ratchet is optional because it is a server runtime, not a requirement for HTTP-only applications.

## gRPC

The official PHP gRPC package provides a client library; it does not provide a native PHP gRPC server. SmartResponse therefore supplies the application handler that a real HTTP/2/protobuf host mounts:

```php
use Quonain\SmartResponse\Runtime\Grpc\GrpcHandler;
use Quonain\SmartResponse\Support\GrpcResponse;

$handler = new GrpcHandler(static function (array $request, array $metadata): GrpcResponse {
    return new GrpcResponse(data: app(OrderController::class)->handle($request));
});

// Mount $handler->handle($request, $metadata) in the selected gRPC host.
```

The host owns HTTP/2 framing and protobuf serialization. RoadRunner, FrankenPHP, or another compatible gRPC server should call `GrpcHandler::handle()` and map its array to the generated protobuf response. This keeps the controller logic shared without pretending the PHP client extension is a server.
