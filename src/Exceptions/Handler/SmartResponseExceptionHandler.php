<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Exceptions\Handler;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Quonain\SmartResponse\Contracts\RequestDetectorInterface;
use Quonain\SmartResponse\Contracts\SmartResponseManagerInterface;

/**
 * Register in your App\Exceptions\Handler or bootstrap/app.php exception handling.
 */
final class SmartResponseExceptionHandler
{
    public function __construct(
        private readonly SmartResponseManagerInterface $manager,
        private readonly RequestDetectorInterface $detector,
        private readonly array $config,
    ) {}

    public function render(Request $request, Throwable $e): ?Response
    {
        if (! $this->detector->expectsApi($request)) {
            return null;
        }

        return match (true) {
            $e instanceof ValidationException => $this->manager->validationError(
                $e->errors(),
                $e->getMessage(),
                (int) ($this->config['status_codes']['validation_error'] ?? 422),
            ),
            $e instanceof AuthenticationException => $this->manager->error(
                $e->getMessage() ?: 'Unauthenticated',
                null,
                (int) ($this->config['status_codes']['unauthorized'] ?? 401),
            ),
            $e instanceof AuthorizationException => $this->manager->error(
                $e->getMessage() ?: 'Forbidden',
                null,
                (int) ($this->config['status_codes']['forbidden'] ?? 403),
            ),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => $this->manager->error(
                'Resource not found',
                null,
                (int) ($this->config['status_codes']['not_found'] ?? 404),
            ),
            $e instanceof HttpException => $this->manager->error(
                $e->getMessage() ?: 'HTTP Error',
                null,
                $e->getStatusCode(),
            ),
            default => $this->manager->error(
                config('app.debug') ? $e->getMessage() : 'Server Error',
                config('app.debug') ? ['trace' => $e->getTraceAsString()] : null,
                (int) ($this->config['status_codes']['server_error'] ?? 500),
            ),
        };
    }
}
