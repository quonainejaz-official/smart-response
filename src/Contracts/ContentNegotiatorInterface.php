<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Contracts;

interface ContentNegotiatorInterface
{
    /**
     * @param list<string> $mediaTypes
     * @return string|null The selected media type, or null when none is acceptable.
     */
    public function negotiate(string $accept, array $mediaTypes): ?string;
}
