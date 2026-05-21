<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Contracts;

use Illuminate\Http\Request;

interface RequestDetectorInterface
{
    public function expectsJson(Request $request): bool;

    public function expectsXml(Request $request): bool;

    public function expectsApi(Request $request): bool;

    public function expectsWeb(Request $request): bool;

    public function getPreferredFormat(Request $request): string;
}
