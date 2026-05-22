<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Facades;

use Illuminate\Support\Facades\Facade;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;

/**
 * @method static \Symfony\Component\HttpFoundation\Response respond(\Quonain\SmartResponse\DTO\SmartResponsePayload $payload, ?\Illuminate\Http\Request $request = null)
 * @method static \Symfony\Component\HttpFoundation\Response success(mixed $data = null, ?string $message = null, array $meta = [], int $status = 200)
 * @method static \Symfony\Component\HttpFoundation\Response error(?string $message = null, mixed $errors = null, int $status = 400, array $meta = [])
 * @method static \Symfony\Component\HttpFoundation\Response validationError(mixed $errors, ?string $message = null, int $status = 422)
 * @method static \Symfony\Component\HttpFoundation\Response created(mixed $data = null, ?string $message = null, array $meta = [])
 * @method static \Symfony\Component\HttpFoundation\Response noContent()
 * @method static \Symfony\Component\HttpFoundation\Response notFound(?string $message = null, mixed $errors = null)
 * @method static \Symfony\Component\HttpFoundation\Response unauthorized(?string $message = null, mixed $errors = null)
 * @method static \Symfony\Component\HttpFoundation\Response forbidden(?string $message = null, mixed $errors = null)
 *
 * @see \Quonain\SmartResponse\Services\SmartResponseManager
 */
final class SmartResponse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmartResponseManagerInterface::class;
    }
}
