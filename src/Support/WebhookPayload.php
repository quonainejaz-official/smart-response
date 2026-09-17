<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

/** Creates signed webhook bodies without coupling the package to a HTTP client. */
final class WebhookPayload
{
    public static function create(string $event, mixed $data = null, array $meta = [], ?string $secret = null): array
    {
        $body = ['event' => $event, 'data' => $data, 'meta' => $meta, 'timestamp' => time()];
        $encoded = json_encode($body, JSON_THROW_ON_ERROR);

        if ($secret !== null) {
            $body['signature'] = hash_hmac('sha256', $encoded, $secret);
        }

        return $body;
    }
}
