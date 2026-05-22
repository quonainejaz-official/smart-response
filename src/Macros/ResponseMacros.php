<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Macros;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\ResponseFactory;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;
use Quonain\SmartResponse\DTO\SmartResponsePayload;

final class ResponseMacros
{
    public static function register(): void
    {
        $smartMacro = function (
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
        };

        JsonResponse::macro('smart', $smartMacro);
        ResponseFactory::macro('smart', $smartMacro);

        ResponseFactory::macro('smartSuccess', function (
            mixed $data = null,
            ?string $message = null,
            array $meta = [],
            int $status = 200,
        ) {
            /** @var SmartResponseManagerInterface $manager */
            $manager = app(SmartResponseManagerInterface::class);

            return $manager->success($data, $message, $meta, $status);
        });

        ResponseFactory::macro('smartError', function (
            ?string $message = null,
            mixed $errors = null,
            int $status = 400,
            array $meta = [],
        ) {
            /** @var SmartResponseManagerInterface $manager */
            $manager = app(SmartResponseManagerInterface::class);

            return $manager->error($message, $errors, $status, $meta);
        });

        ResponseFactory::macro('smartCreated', function (
            mixed $data = null,
            ?string $message = null,
            array $meta = [],
        ) {
            return app(SmartResponseManagerInterface::class)->created($data, $message, $meta);
        });

        ResponseFactory::macro('smartNotFound', function (?string $message = null, mixed $errors = null) {
            return app(SmartResponseManagerInterface::class)->notFound($message, $errors);
        });
    }
}
