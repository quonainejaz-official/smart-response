<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Vendor\SmartResponse\DTO\SmartResponsePayload;

interface WebResponseBuilderInterface
{
    public function view(SmartResponsePayload $payload): Response;

    public function redirect(SmartResponsePayload $payload): RedirectResponse;
}
