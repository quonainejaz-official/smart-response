<?php

declare(strict_types=1);

namespace Quonain\SmartResponse\Detectors;

use Illuminate\Http\Request;
use Quonain\SmartResponse\Contracts\RequestDetectorInterface;

final class RequestTypeDetector implements RequestDetectorInterface
{
    public function __construct(
        private readonly array $config,
    ) {}

    public function expectsJson(Request $request): bool
    {
        $accept = strtolower($request->header('Accept', ''));

        foreach ($this->config['detection']['json_accept'] as $type) {
            if (str_contains($accept, strtolower($type))) {
                return true;
            }
        }

        if ($this->isApiRoute($request)) {
            return true;
        }

        if ($request->ajax() && ! $request->pjax()) {
            return true;
        }

        if ($request->expectsJson()) {
            return true;
        }

        return false;
    }

    public function expectsXml(Request $request): bool
    {
        $accept = strtolower($request->header('Accept', ''));

        foreach ($this->config['detection']['xml_accept'] as $type) {
            if (str_contains($accept, strtolower($type))) {
                return true;
            }
        }

        return false;
    }

    public function expectsApi(Request $request): bool
    {
        if ($this->config['graphql']['enabled'] ?? false) {
            $graphqlAccept = $this->config['graphql']['accept'] ?? '';
            if (str_contains(strtolower($request->header('Accept', '')), strtolower($graphqlAccept))) {
                return true;
            }
        }

        return $this->expectsJson($request) || $this->expectsXml($request);
    }

    public function expectsWeb(Request $request): bool
    {
        return ! $this->expectsApi($request);
    }

    public function getPreferredFormat(Request $request): string
    {
        if ($this->expectsXml($request)) {
            return 'xml';
        }

        return $this->config['default_format'] ?? 'json';
    }

    private function isApiRoute(Request $request): bool
    {
        $path = trim($request->path(), '/');
        $prefixes = $this->config['detection']['api_route_prefixes'] ?? [];

        foreach ($prefixes as $prefix) {
            if ($request->is($prefix) || $request->is($prefix.'/*')) {
                return true;
            }

            if ($request->segment(1) === $prefix) {
                return true;
            }

            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        $patterns = $this->config['detection']['api_route_patterns'] ?? [];

        foreach ($patterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
