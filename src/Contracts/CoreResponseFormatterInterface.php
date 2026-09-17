<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Contracts;

use Quonain\SmartResponse\Core\Response;

/** Framework-independent formatter contract for the standalone core. */
interface CoreResponseFormatterInterface
{
    public function mediaType(): string;

    /** @param array<string, mixed> $context */
    public function format(mixed $data, array $context = []): Response;
}
