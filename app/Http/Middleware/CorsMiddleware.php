<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredOrigins = collect([
            config('app.frontend_url'),
            env('FRONTEND_URL'),
            env('FRONTEND_URLS'),
            'http://localhost:3000',
            'http://localhost:3001',
        ])
            ->filter()
            ->flatMap(fn ($value) => is_string($value) ? array_map('trim', explode(',', $value)) : [])
            ->unique()
            ->values();

        $requestOrigin = $request->headers->get('Origin');

        if ($requestOrigin && ($configuredOrigins->isEmpty() || $configuredOrigins->contains($requestOrigin))) {
            $allowedOrigin = $requestOrigin;
        } elseif ($configuredOrigins->isNotEmpty()) {
            $allowedOrigin = $configuredOrigins->first();
        } else {
            $allowedOrigin = null;
        }

        if ($request->getMethod() === 'OPTIONS') {
            $response = response()->noContent(204);
        } else {
            $response = $next($request);
        }

        if ($allowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
        }
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Authorization, X-Requested-With, Accept');
        $response->headers->set('Access-Control-Allow-Credentials', $allowedOrigin ? 'true' : 'false');
        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}
