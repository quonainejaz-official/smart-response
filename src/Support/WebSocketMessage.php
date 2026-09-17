<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

/** Transport-neutral WebSocket message; send encode() through Laravel Reverb, Echo, or another server. */
final class WebSocketMessage
{
    /** @param array<string, mixed> $meta */
    public function __construct(
        public readonly mixed $data = null,
        public readonly ?string $message = null,
        public readonly bool $success = true,
        public readonly mixed $errors = null,
        public readonly array $meta = [],
        public readonly ?string $event = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['success' => $this->success, 'message' => $this->message, 'data' => $this->data, 'meta' => $this->meta, 'errors' => $this->errors, 'event' => $this->event];
    }

    public function encode(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
