<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Events;

use Symfony\Component\HttpFoundation\Response;
use Vendor\SmartResponse\DTO\SmartResponsePayload;

final class SmartResponsePrepared
{
    public function __construct(
        public SmartResponsePayload $payload,
        public Response $response,
    ) {}
}
