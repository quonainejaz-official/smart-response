<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default API Response Structure Keys
    |--------------------------------------------------------------------------
    */
    'api' => [
        'success_key' => 'success',
        'message_key' => 'message',
        'data_key' => 'data',
        'meta_key' => 'meta',
        'errors_key' => 'errors',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Detection
    |--------------------------------------------------------------------------
    */
    'detection' => [
        'json_accept' => ['application/json', 'application/vnd.api+json'],
        'xml_accept' => ['application/xml', 'text/xml'],
        'api_route_prefixes' => ['api'],
        'api_route_patterns' => ['api/*'],
        'format_header' => 'X-Smart-Response-Format',
        'format_query_parameter' => 'format',
        'format_route_suffixes' => ['json', 'xml', 'legacy', 'graphql', 'soap'],
        'bearer_as_api' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Meta Enrichment
    |--------------------------------------------------------------------------
    */
    'meta' => [
        'enabled' => true,
        'include_timestamp' => true,
        'include_request_id' => true,
        'request_id_header' => 'X-Request-Id',
        'include_api_version' => false,
        'api_version' => '1.0',
        'api_version_header' => 'X-API-Version',
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Formats
    |--------------------------------------------------------------------------
    | Supported: json, xml, legacy, graphql, soap
    */
    'default_format' => 'json',
    'api_formats' => ['json', 'xml', 'legacy', 'graphql', 'soap'],

    /* Named response contracts. Per-response values override profile values. */
    'profiles' => [
        'modern-api' => ['format' => 'json'],
        'legacy-v1' => ['format' => 'legacy'],
    ],

    /*
    | Legacy API response shape (selected with format: 'legacy').
    | Set custom key names when integrating an existing API contract.
    */
    'legacy' => [
        'keys' => [
            'status' => 'status',
            'message' => 'message',
            'data' => 'data',
            'errors' => 'errors',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Status Codes
    |--------------------------------------------------------------------------
    */
    'status_codes' => [
        'success' => 200,
        'created' => 201,
        'no_content' => 204,
        'validation_error' => 422,
        'unauthorized' => 401,
        'forbidden' => 403,
        'not_found' => 404,
        'server_error' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Web / Blade
    |--------------------------------------------------------------------------
    */
    'web' => [
        'flash_success_key' => 'success',
        'flash_error_key' => 'error',
        'flash_toast_key' => 'toast',
        'default_redirect_route' => 'home',
    ],

    /*
    |--------------------------------------------------------------------------
    | Inertia.js
    |--------------------------------------------------------------------------
    */
    'inertia' => [
        'enabled' => false,
        'adapter' => \Quonain\SmartResponse\Support\InertiaAdapter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    */
    'livewire' => [
        'enabled' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    */
    'locale' => [
        'enabled' => true,
        'fallback' => 'en',
        'message_prefix' => 'smart-response',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => false,
        'channel' => null,
        'level' => 'info',
        'redact' => ['authorization', 'cookie', 'set-cookie', 'password', 'token', 'secret', 'api-key'],
        'sample_body' => false,
    ],

    /* Named outbound API providers. Credentials should come from env/config. */
    'http' => [
        'providers' => [],
        'max_response_bytes' => 10485760,
        'max_redirects' => 3,
        'circuit_breaker' => ['enabled' => false, 'failures' => 5, 'cooldown_seconds' => 30],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Caching (API only)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => false,
        'ttl' => 60,
        'store' => null,
        'prefix' => 'smart_response',
        // Never cache user-specific responses unless the application explicitly
        // enables it and supplies a safe cache key.
        'cache_authenticated' => false,
        'vary_headers' => ['Accept'],
        'cacheable_statuses' => [200],
        'etag' => true,
        'last_modified' => true,
        'headers' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Response Helpers
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'enabled' => false,
        'message' => 'Too many requests. Please try again later.',
        'status' => 429,
        'retry_after_seconds' => 60,
        'max_attempts' => 60,
        'decay_seconds' => 60,
        'prefix' => 'smart_response:rate_limit',
        // Supported values: user_or_ip, ip, route.
        'key' => 'user_or_ip',
        'store' => null,
    ],

    /* Reject declared oversized requests before controller work begins. */
    'payload_limits' => [
        'enabled' => false,
        'max_bytes' => 1048576,
        'status' => 413,
    ],

    /* Existing headers are never overwritten. Enable only after reviewing CSP. */
    'security_headers' => [
        'enabled' => false,
        'headers' => [
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Frame-Options' => 'SAMEORIGIN',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    */
    'events' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'alias' => 'smart.response',
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | GraphQL compatibility
    |--------------------------------------------------------------------------
    */
    'graphql' => [
        'enabled' => false,
        'accept' => 'application/graphql-response+json',
    ],

];
