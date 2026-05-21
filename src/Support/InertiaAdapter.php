<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Support;

use Illuminate\Http\Response;
use Vendor\SmartResponse\DTO\SmartResponsePayload;

/**
 * Optional Inertia.js adapter. Enable in config when inertiajs/inertia-laravel is installed.
 */
final class InertiaAdapter
{
    public function render(SmartResponsePayload $payload): ?Response
    {
        if (! $payload->useInertia && ! ($payload->inertiaComponent)) {
            return null;
        }

        if (! class_exists(\Inertia\Inertia::class)) {
            return null;
        }

        $component = $payload->inertiaComponent ?? $payload->view;

        if ($component === null) {
            return null;
        }

        $data = array_merge(
            $payload->viewData ?? [],
            ['data' => $payload->normalizedData()],
            $payload->message ? ['message' => $payload->message] : [],
        );

        return \Inertia\Inertia::render($component, $data)->toResponse($payload->request);
    }
}
