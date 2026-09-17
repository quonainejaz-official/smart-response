<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Core\Formatters;

use Quonain\SmartResponse\Contracts\CoreResponseFormatterInterface;
use Quonain\SmartResponse\Core\Response;

final class TextFormatter implements CoreResponseFormatterInterface
{
    public function mediaType(): string { return 'text/plain'; }

    /** @param array<string, mixed> $context */
    public function format(mixed $data, array $context = []): Response
    {
        $body = is_string($data) ? $data : (string) $data;

        return new Response($body, (int) ($context['status'] ?? 200), [
            'Content-Type' => $this->mediaType().'; charset=UTF-8',
            ...($context['headers'] ?? []),
        ]);
    }
}
