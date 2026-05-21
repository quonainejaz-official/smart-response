<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Macros;

use Illuminate\Http\JsonResponse;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

final class ResponseMacros
{
    public static function register(): void
    {
        JsonResponse::macro('smart', function (
            mixed $data = null,
            ?string $message = null,
            bool $success = true,
            mixed $errors = null,
            array $meta = [],
            int $status = 200,
        ) {
            /** @var SmartResponseManagerInterface $manager */
            $manager = app(SmartResponseManagerInterface::class);

            return $manager->respond(new SmartResponsePayload(
                data: $data,
                message: $message,
                success: $success,
                errors: $errors,
                meta: $meta,
                status: $status,
            ));
        });
    }
}
