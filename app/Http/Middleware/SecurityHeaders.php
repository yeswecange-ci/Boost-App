<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * CDN autorisés (Alpine.js, Font Awesome, Google Fonts).
     * Adaptez cette liste si vous ajoutez des CDN externes.
     */
    private const SCRIPT_SOURCES = [
        "'self'",
        "'unsafe-inline'",       // Requis pour Alpine.js (x-data) et @push('scripts')
        'cdn.jsdelivr.net',
        'cdnjs.cloudflare.com',
        'unpkg.com',
    ];

    private const STYLE_SOURCES = [
        "'self'",
        "'unsafe-inline'",       // Requis pour Tailwind CSS inline
        'fonts.googleapis.com',
        'cdnjs.cloudflare.com',
    ];

    private const FONT_SOURCES = [
        "'self'",
        'fonts.gstatic.com',
        'cdnjs.cloudflare.com',
        'data:',
    ];

    private const IMG_SOURCES = [
        "'self'",
        'data:',
        'https:',                // Images distantes (thumbnails Facebook, etc.)
        'blob:',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ne pas écraser les headers si c'est une réponse de téléchargement
        if ($response->headers->has('Content-Disposition')) {
            return $response;
        }

        $scriptSrc = implode(' ', self::SCRIPT_SOURCES);
        $styleSrc  = implode(' ', self::STYLE_SOURCES);
        $fontSrc   = implode(' ', self::FONT_SOURCES);
        $imgSrc    = implode(' ', self::IMG_SOURCES);

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src {$fontSrc}",
            "img-src {$imgSrc}",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);

        $response->headers->set('Content-Security-Policy', $csp);

        // Empêche le clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Empêche le MIME-type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Référent limité au strict minimum
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Désactive les fonctionnalités navigateur non utilisées
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=()'
        );

        // HSTS — force HTTPS pour 1 an (activer uniquement si le site est 100% HTTPS)
        if ($request->secure() || config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Supprime le header qui révèle la techno backend
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
