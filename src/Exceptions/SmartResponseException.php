<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Exceptions;

use Exception;

final class SmartResponseException extends Exception
{
    public static function missingView(): self
    {
        return new self('A view name is required for web SmartResponse output.');
    }

    public static function invalidFormat(string $format): self
    {
        return new self("Unsupported SmartResponse format: {$format}");
    }
}
