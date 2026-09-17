<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Runtime\WebSocket;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Closure;
use SplObjectStorage;
use Throwable;
use Quonain\SmartResponse\Support\WebSocketMessage;

/** Real Ratchet component that normalizes incoming/outgoing WebSocket messages. */
final class SmartResponseWebSocketComponent implements MessageComponentInterface
{
    /** @var SplObjectStorage<object, null> */
    private SplObjectStorage $connections;

    /** @param callable $handler */
    public function __construct(callable $handler)
    {
        $this->handler = Closure::fromCallable($handler);
        $this->connections = new SplObjectStorage();
    }

    /** @var Closure */
    private readonly Closure $handler;

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->connections->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, mixed $msg): void
    {
        try {
            $request = json_decode((string) $msg, true, 512, JSON_THROW_ON_ERROR);
            $response = ($this->handler)(is_array($request) ? $request : ['data' => $request], $from);
            $from->send($response instanceof WebSocketMessage ? $response->encode() : (is_string($response) ? $response : json_encode($response, JSON_THROW_ON_ERROR)));
        } catch (Throwable $exception) {
            $from->send((new WebSocketMessage(
                message: $exception->getMessage(),
                success: false,
                errors: ['class' => $exception::class],
            ))->encode());
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->connections->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->connections->detach($conn);
        $conn->close();
    }
}
