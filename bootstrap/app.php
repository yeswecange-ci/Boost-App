<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ── Proxies ──────────────────────────────────────────────────────────
        // Faire confiance uniquement aux plages IP privées (Docker / Coolify / Traefik).
        // Ne JAMAIS utiliser '*' en production : cela permet de forger X-Forwarded-For.
        $middleware->trustProxies(
            at: '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // ── Alias middleware ──────────────────────────────────────────────────
        $middleware->alias([
            'role'  => \App\Http\Middleware\CheckRole::class,
            'audit' => \App\Http\Middleware\AuditLog::class,
            '2fa'   => \App\Http\Middleware\TwoFactor::class,
        ]);

        // ── Security headers sur toutes les réponses web ──────────────────────
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // ── Audit log sur les mutations ───────────────────────────────────────
        $middleware->append(\App\Http\Middleware\AuditLog::class);

        // ── CSRF : routes webhook exclues (authentifiées par secret header) ───
        $middleware->validateCsrfTokens(except: [
            'webhook/n8n/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
