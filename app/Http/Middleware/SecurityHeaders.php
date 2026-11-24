<?php

// Matjar — © 2026 Fedaa Mohamed. All rights reserved. Proprietary.

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a strict, nonce-based Content-Security-Policy and the common hardening
 * headers to every response. The nonce is generated before the request is
 * handled so the Vite-generated <script>/<link> tags carry it.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = $this->makeNonce();

        Vite::useCspNonce($nonce);
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        return self::apply($response, $request, $nonce);
    }

    /**
     * Apply the hardening headers to any response. Used by the middleware for
     * normal responses and by the exception handler for error responses.
     */
    public static function apply(Response $response, Request $request, ?string $nonce = null): Response
    {
        $nonce ??= $request->attributes->get('csp_nonce')
            ?? Vite::cspNonce()
            ?? self::makeNonce();

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            // Alpine's CSP build is used (no eval / new Function), so scripts
            // are nonce-gated without 'unsafe-inline' or 'unsafe-eval'.
            "script-src 'self' 'nonce-{$nonce}'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ]));

        if ($request->is('admin') || $request->is('admin/*') || $request->is('account') || $request->is('account/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    private static function makeNonce(): string
    {
        return base64_encode(random_bytes(18));
    }
}
