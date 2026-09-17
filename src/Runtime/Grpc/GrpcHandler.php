<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Runtime\Grpc;

use Closure;
use Quonain\SmartResponse\Support\GrpcResponse;

/**
 * Protocol-neutral gRPC handler for a real host runtime such as RoadRunner or
 * FrankenPHP. The host owns HTTP/2 and protobuf; this class owns application
 * response normalization.
 */
final class GrpcHandler
{
    /** @param callable $handler */
    public function __construct(callable $handler)
    {
        $this->handler = Closure::fromCallable($handler);
    }

    /** @var Closure */
    private readonly Closure $handler;

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    public function handle(array $request, array $metadata = []): array
    {
        $response = ($this->handler)($request, $metadata);

        return $response instanceof GrpcResponse ? $response->toArray() : $response;
    }
}
