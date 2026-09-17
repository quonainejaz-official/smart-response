<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core;

use Quonain\SmartResponse\Contracts\ContentNegotiatorInterface;

/** Small RFC-style media-type negotiator for the core; use willdurand/negotiation for richer policies. */
final class AcceptHeaderNegotiator implements ContentNegotiatorInterface
{
    /** @param list<string> $mediaTypes */
    public function negotiate(string $accept, array $mediaTypes): ?string
    {
        if ($mediaTypes === []) {
            return null;
        }

        $ranges = [];
        foreach (explode(',', $accept !== '' ? $accept : '*/*') as $position => $part) {
            $segments = array_map('trim', explode(';', strtolower($part)));
            $range = array_shift($segments);
            if ($range === '') {
                $range = '*/*';
            }
            $quality = 1.0;
            foreach ($segments as $segment) {
                if (str_starts_with($segment, 'q=')) {
                    $quality = max(0.0, min(1.0, (float) substr($segment, 2)));
                }
            }
            $ranges[] = [$range, $quality, $position];
        }

        usort($ranges, static fn (array $a, array $b): int => $b[1] <=> $a[1] ?: $a[2] <=> $b[2]);
        foreach ($ranges as [$range, $quality]) {
            if ($quality === 0.0) {
                continue;
            }
            foreach ($mediaTypes as $mediaType) {
                $candidate = strtolower($mediaType);
                if ($range === '*/*' || $range === $candidate || (str_ends_with($range, '/*') && str_starts_with($candidate, substr($range, 0, -1)))) {
                    return $mediaType;
                }
            }
        }

        return null;
    }
}
