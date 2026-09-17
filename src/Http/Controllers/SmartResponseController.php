<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Http\Controllers;

use Quonain\SmartResponse\Traits\HasSmartResponse;

/** Optional base controller for applications that want the smart helpers. */
abstract class SmartResponseController
{
    use HasSmartResponse;
}
