<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Contracts;

use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Vendor\SmartResponse\DTO\SmartResponsePayload;

interface ResponseFormatterInterface
{
    public function format(SmartResponsePayload $payload): BaseResponse;
}
