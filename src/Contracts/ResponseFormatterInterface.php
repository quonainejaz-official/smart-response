<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Contracts;

use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

interface ResponseFormatterInterface
{
    public function format(SmartResponsePayload $payload): BaseResponse;
}
