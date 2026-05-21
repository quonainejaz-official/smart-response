<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Contracts;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

interface WebResponseBuilderInterface
{
    public function view(SmartResponsePayload $payload): Response;

    public function redirect(SmartResponsePayload $payload): RedirectResponse;
}
