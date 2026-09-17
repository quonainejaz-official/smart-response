<?php

namespace Ratchet;

class App
{
    public function __construct(string $host = 'localhost', int $port = 8080) {}

    /** @param array<int, string> $allowedOrigins */
    public function route(string $path, MessageComponentInterface $controller, array $allowedOrigins = []): void {}

    public function run(): void {}
}

interface ConnectionInterface
{
    public function send(mixed $data): mixed;
    public function close(): mixed;
}

interface MessageComponentInterface
{
    public function onOpen(ConnectionInterface $conn): void;
    public function onMessage(ConnectionInterface $from, mixed $msg): void;
    public function onClose(ConnectionInterface $conn): void;
    public function onError(ConnectionInterface $conn, \Exception $e): void;
}
