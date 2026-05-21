<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Builders;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Vendor\SmartResponse\Contracts\WebResponseBuilderInterface;
use Vendor\SmartResponse\DTO\SmartResponsePayload;
use Vendor\SmartResponse\Support\InertiaAdapter;

final class WebResponseBuilder implements WebResponseBuilderInterface
{
    public function __construct(
        private readonly UrlGenerator $url,
        private readonly InertiaAdapter $inertiaAdapter,
        private readonly array $config,
    ) {}

    public function view(SmartResponsePayload $payload): Response
    {
        if ($this->shouldUseInertia($payload)) {
            $inertiaResponse = $this->inertiaAdapter->render($payload);

            if ($inertiaResponse !== null) {
                return $inertiaResponse;
            }
        }

        $viewName = $payload->view;

        if ($viewName === null) {
            abort(500, 'SmartResponse: view name is required for web responses.');
        }

        $data = $this->resolveViewData($payload);

        return response()->view($viewName, $data, $payload->status);
    }

    public function redirect(SmartResponsePayload $payload): RedirectResponse
    {
        $redirect = $this->resolveRedirectUrl($payload);

        $response = redirect()->to($redirect, $payload->status);

        if ($payload->flash) {
            $this->applyFlash($response, $payload);
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveViewData(SmartResponsePayload $payload): array
    {
        $base = $payload->viewData ?? [];
        $normalized = $payload->normalizedData();

        if (is_array($normalized) && array_is_list($normalized) === false) {
            return array_merge($base, $normalized);
        }

        return array_merge($base, [
            'data' => $normalized,
            'message' => $payload->message,
            'meta' => $payload->meta,
            'success' => $payload->success,
            'errors' => $payload->errors,
        ]);
    }

    private function resolveRedirectUrl(SmartResponsePayload $payload): string
    {
        if ($payload->redirect !== null) {
            return $payload->redirect;
        }

        if ($payload->route !== null) {
            return $this->url->route($payload->route, $payload->routeParameters);
        }

        return $this->url->route($this->config['web']['default_redirect_route'] ?? 'home');
    }

    private function applyFlash(RedirectResponse $response, SmartResponsePayload $payload): void
    {
        $web = $this->config['web'];

        if ($payload->message) {
            $key = $payload->success
                ? ($web['flash_success_key'] ?? 'success')
                : ($web['flash_error_key'] ?? 'error');

            $response->with($key, $payload->message);
        }

        if ($payload->toast) {
            $response->with($web['flash_toast_key'] ?? 'toast', [
                'message' => $payload->message,
                'type' => $payload->success ? 'success' : 'error',
            ]);
        }

        if ($payload->errors) {
            $response->withErrors($payload->errors);
        }
    }

    private function shouldUseInertia(SmartResponsePayload $payload): bool
    {
        return $payload->useInertia
            || ($this->config['inertia']['enabled'] ?? false)
            || $payload->inertiaComponent !== null;
    }
}
