<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Events;

use Quonain\SmartResponse\DTO\SmartResponsePayload;

final class SmartResponsePreparing
{
    public function __construct(
        public SmartResponsePayload $payload,
    ) {}
}
