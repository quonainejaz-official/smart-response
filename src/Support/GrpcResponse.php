<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

/** Transport-neutral gRPC response value object; map it in any gRPC server adapter. */
final class GrpcResponse
{
    /** @param array<string, mixed> $meta */
    public function __construct(
        public readonly mixed $data = null,
        public readonly ?string $message = null,
        public readonly bool $success = true,
        public readonly mixed $errors = null,
        public readonly array $meta = [],
        public readonly int $status = 0,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['success' => $this->success, 'message' => $this->message, 'data' => $this->data, 'meta' => $this->meta, 'errors' => $this->errors];
    }
}
