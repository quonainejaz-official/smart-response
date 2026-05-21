<?php

declare(strict_types=1);

namespace Vendor\SmartResponse\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional middleware — attaches Accept header hints or request attributes.
 */
final class SmartResponseMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('smart_response', true);

        return $next($request);
    }
}
