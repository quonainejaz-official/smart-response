<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

final class MetaEnricher
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function enrich(SmartResponsePayload $payload, Request $request): SmartResponsePayload
    {
        $metaConfig = $this->config['meta'] ?? [];

        if (! ($metaConfig['enabled'] ?? false)) {
            return $payload;
        }

        $extra = [];

        if ($metaConfig['include_timestamp'] ?? true) {
            $extra['timestamp'] = now()->toIso8601String();
        }

        if ($metaConfig['include_request_id'] ?? true) {
            $header = $metaConfig['request_id_header'] ?? 'X-Request-Id';
            $extra['request_id'] = $request->header($header) ?: (string) Str::uuid();
        }

        if ($metaConfig['include_api_version'] ?? false) {
            $versionHeader = $metaConfig['api_version_header'] ?? 'X-API-Version';
            $extra['api_version'] = $request->header($versionHeader)
                ?? $metaConfig['api_version']
                ?? null;
        }

        $extra = array_filter($extra, static fn ($value) => $value !== null);

        if ($extra === []) {
            return $payload;
        }

        return $payload->withMeta($extra);
    }
}
