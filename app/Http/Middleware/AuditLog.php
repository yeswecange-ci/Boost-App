<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Journalise les actions sensibles (mutations sur les ressources critiques).
 * Les logs vont dans le canal Laravel configuré (LOG_CHANNEL).
 */
class AuditLog
{
    /**
     * Méthodes HTTP considérées comme mutations.
     */
    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Patterns de routes à auditer (regex sur l'URL).
     */
    private const AUDITED_PATTERNS = [
        '#^/boost#',
        '#^/campaigns#',
        '#^/users#',
        '#^/admin#',
        '#^/settings#',
        '#^/profile#',
        '#^/webhook#',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), self::MUTATING_METHODS, true)) {
            return $response;
        }

        $url = $request->getPathInfo();
        $shouldAudit = false;

        foreach (self::AUDITED_PATTERNS as $pattern) {
            if (preg_match($pattern, $url)) {
                $shouldAudit = true;
                break;
            }
        }

        if ($shouldAudit) {
            Log::channel(config('logging.default'))->info('[AUDIT]', [
                'user_id'    => auth()->id() ?? 'unauthenticated',
                'user_email' => auth()->user()?->email ?? '-',
                'method'     => $request->method(),
                'url'        => $url,
                'ip'         => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 200),
                'status'     => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
