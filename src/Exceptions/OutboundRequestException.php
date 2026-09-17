<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Exceptions;

final class OutboundRequestException extends SmartResponseException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'external_api_error',
        public readonly ?int $status = null,
        public readonly ?string $provider = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }
}
