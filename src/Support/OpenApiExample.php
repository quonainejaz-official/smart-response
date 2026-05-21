<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Support;

/**
 * Swagger / OpenAPI example payloads for documentation generators.
 */
final class OpenApiExample
{
    /**
     * @return array<string, mixed>
     */
    public static function successExample(): array
    {
        return [
            'success' => true,
            'message' => 'Users fetched successfully',
            'data' => [],
            'meta' => (object) [],
            'errors' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function errorExample(): array
    {
        return [
            'success' => false,
            'message' => 'Something went wrong',
            'data' => null,
            'meta' => (object) [],
            'errors' => ['field' => ['The field is required.']],
        ];
    }
}
