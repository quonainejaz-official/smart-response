<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Events;

use Vendor\SmartResponse\DTO\SmartResponsePayload;

final class SmartResponsePreparing
{
    public function __construct(
        public SmartResponsePayload $payload,
    ) {}
}
