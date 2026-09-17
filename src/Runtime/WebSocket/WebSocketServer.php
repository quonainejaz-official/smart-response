<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Runtime\WebSocket;

use Ratchet\App;
use RuntimeException;

/**
 * Starts a real Ratchet WebSocket server around SmartResponse's component.
 * Ratchet remains optional so applications can choose another WebSocket host.
 */
final class WebSocketServer
{
    /** @param callable $handler */
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $path,
        private readonly mixed $handler,
    ) {}

    public function run(): void
    {
        if (!class_exists(App::class)) {
            throw new RuntimeException('Install cboden/ratchet to run SmartResponse WebSocketServer.');
        }

        $app = new App($this->host, $this->port);
        $app->route($this->path, new SmartResponseWebSocketComponent($this->handler), ['*']);
        $app->run();
    }
}
