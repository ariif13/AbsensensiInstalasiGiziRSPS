<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecurityHeaders
{
    private function viteDevOrigins(): array
    {
        $origins = ['http://127.0.0.1:5173', 'http://localhost:5173'];

        $hotFile = public_path('hot');
        if (is_file($hotFile)) {
            $hotUrl = trim((string) file_get_contents($hotFile));
            if ($hotUrl !== '') {
                $origins[] = rtrim($hotUrl, '/');
            }
        }

        return array_values(array_unique($origins));
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Core Security Headers
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // HSTS - Force HTTPS (1 year)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Permissions Policy - Restrict sensitive browser features
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

        $scriptSrc = ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://unpkg.com', 'https://cdn.jsdelivr.net'];
        $styleSrc = ["'self'", "'unsafe-inline'", 'https://unpkg.com', 'https://fonts.googleapis.com', 'https://cdn.jsdelivr.net', 'https://fonts.bunny.net'];
        $connectSrc = ["'self'", 'https://tile.openstreetmap.org', 'https://cdn.jsdelivr.net', 'wss:'];

        if (app()->environment('local')) {
            $viteOrigins = $this->viteDevOrigins();
            $scriptSrc = array_merge($scriptSrc, $viteOrigins);
            $styleSrc = array_merge($styleSrc, $viteOrigins);
            $connectSrc = array_merge($connectSrc, $viteOrigins);

            foreach ($viteOrigins as $origin) {
                $connectSrc[] = preg_replace('/^http:/', 'ws:', $origin);
            }
        }

        $csp = implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', array_values(array_unique($scriptSrc))),
            'style-src '.implode(' ', array_values(array_unique($styleSrc))),
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
            "img-src 'self' data: blob: https: http:",
            'connect-src '.implode(' ', array_values(array_unique($connectSrc))),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
