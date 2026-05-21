<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Events;

use Symfony\Component\HttpFoundation\Response;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

final class SmartResponsePrepared
{
    public function __construct(
        public SmartResponsePayload $payload,
        public Response $response,
    ) {}
}
