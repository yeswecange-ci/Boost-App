<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware 2FA — deux cas gérés :
 *
 *  1. 2FA activé (secret en DB) + non vérifié en session
 *     → redirect vers /2fa/verify (saisie du code TOTP)
 *
 *  2. 2FA requis (two_factor_required = true) mais pas encore configuré
 *     → redirect vers /2fa/setup (configuration initiale obligatoire)
 */
class TwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        // Routes 2FA et logout exemptées pour éviter la boucle infinie
        if ($request->routeIs('2fa.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        $user = auth()->user();

        // Cas 1 : 2FA configuré et activé → doit valider le code TOTP en session
        if ($user->two_factor_enabled) {
            if (! $request->session()->get('two_factor_verified')) {
                return redirect()->route('2fa.verify');
            }
            return $next($request);
        }

        // Cas 2 : 2FA pas encore configuré mais obligatoire → forcer le setup
        if ($user->two_factor_required) {
            return redirect()->route('2fa.setup')
                ->with('info', 'La double authentification est obligatoire. Veuillez la configurer pour continuer.');
        }

        return $next($request);
    }
}
