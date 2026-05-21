<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

use Illuminate\Contracts\Translation\Translator;

final class MessageTranslator
{
    public function __construct(
        private readonly Translator $translator,
        private readonly array $config,
    ) {}

    public function translate(?string $message, ?string $locale = null): ?string
    {
        if ($message === null || $message === '') {
            return $message;
        }

        if (! ($this->config['locale']['enabled'] ?? true)) {
            return $message;
        }

        $prefix = $this->config['locale']['message_prefix'] ?? 'smart-response';
        $key = "{$prefix}.{$message}";

        if ($this->translator->has($key, $locale)) {
            return $this->translator->get($key, [], $locale);
        }

        return $message;
    }
}
