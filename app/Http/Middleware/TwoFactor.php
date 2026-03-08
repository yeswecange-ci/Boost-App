<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware 2FA — s'assure que l'utilisateur a validé son code TOTP
 * si la double authentification est activée sur son compte.
 *
 * Flux :
 *   login réussi → session créée → ce middleware intercepte toutes les
 *   requêtes → si 2FA activé et non vérifié → redirect vers /2fa/verify.
 */
class TwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        // Pas connecté → le middleware auth s'en occupe
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // 2FA non activé sur ce compte → accès libre
        if (! $user->two_factor_enabled) {
            return $next($request);
        }

        // 2FA déjà vérifié dans cette session → accès libre
        if ($request->session()->get('two_factor_verified')) {
            return $next($request);
        }

        // Routes 2FA et logout exemptées pour éviter la boucle infinie
        if ($request->routeIs('2fa.*') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect()->route('2fa.verify');
    }
}
