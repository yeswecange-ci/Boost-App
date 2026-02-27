<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    /**
     * Affiche la page des paramètres.
     */
    public function index()
    {
        $n8n  = $this->getGroup('n8n');
        $meta = $this->getGroup('meta');

        return view('settings.index', compact('n8n', 'meta'));
    }

    /**
     * Sauvegarde les paramètres N8N.
     */
    public function updateN8n(Request $request)
    {
        // PHP converts dots in field names to underscores in $_POST,
        // so name="n8n.webhook_create" arrives as n8n_webhook_create.
        $request->validate([
            'n8n_webhook_create'   => 'nullable|url',
            'n8n_webhook_activate' => 'nullable|url',
            'n8n_webhook_pause'    => 'nullable|url',
            'n8n_secret'           => 'nullable|string|max:255',
            'n8n_timeout'          => 'nullable|integer|min:3|max:60',
        ]);

        SettingService::setMany([
            'n8n.mock_mode'        => $request->boolean('n8n_mock_mode') ? 'true' : 'false',
            'n8n.webhook_create'   => $request->input('n8n_webhook_create'),
            'n8n.webhook_activate' => $request->input('n8n_webhook_activate'),
            'n8n.webhook_pause'    => $request->input('n8n_webhook_pause'),
            'n8n.secret'           => $request->input('n8n_secret'),
            'n8n.timeout'          => $request->input('n8n_timeout', 10),
        ]);

        return redirect()->route('settings.index')
                         ->with('success', 'Configuration N8N sauvegardée.');
    }

    /**
     * Sauvegarde les paramètres Meta.
     */
    public function updateMeta(Request $request)
    {
        // PHP converts dots in field names to underscores in $_POST,
        // so name="meta.app_id" arrives as meta_app_id.
        $request->validate([
            'meta_app_id'       => 'nullable|string|max:50',
            'meta_app_secret'   => 'nullable|string|max:255',
            'meta_access_token' => 'nullable|string|max:1000',
            'meta_api_version'  => 'nullable|string|regex:/^v\d+\.\d+$/',
        ]);

        SettingService::setMany([
            'meta.mock_mode'     => $request->boolean('meta_mock_mode') ? 'true' : 'false',
            'meta.app_id'        => $request->input('meta_app_id'),
            'meta.app_secret'    => $request->input('meta_app_secret'),
            'meta.access_token'  => $request->input('meta_access_token'),
            'meta.api_version'   => $request->input('meta_api_version', 'v21.0'),
        ]);

        return redirect()->route('settings.index')
                         ->with('success', 'Configuration Meta sauvegardée.');
    }

    /**
     * Test live — envoie un ping au webhook N8N.
     * Retourne JSON.
     */
    public function testN8n(Request $request)
    {
        $url     = SettingService::get('n8n.webhook_create');
        $secret  = SettingService::get('n8n.secret');
        $timeout = SettingService::int('n8n.timeout', 10);

        if (!$url) {
            return response()->json([
                'success' => false,
                'message' => 'URL du webhook non configurée.',
            ]);
        }

        $testPayload = [
            'boost_id'     => 0,
            'test'         => true,
            'message'      => 'Ping depuis Boost Manager — test de connexion',
            'callback_url' => route('webhook.n8n.boost-created'),
            'sent_at'      => now()->toIso8601String(),
        ];

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-N8N-Secret' => $secret ?? '',
                ])
                ->post($url, $testPayload);

            return response()->json([
                'success'  => $response->successful(),
                'status'   => $response->status(),
                'message'  => $response->successful()
                    ? 'Connexion N8N réussie ! (HTTP ' . $response->status() . ')'
                    : 'N8N a répondu avec une erreur HTTP ' . $response->status(),
                'response' => $response->json() ?? $response->body(),
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de joindre N8N : ' . $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Test live — vérifie le token Meta en appelant /me.
     * Retourne JSON.
     */
    public function testMeta(Request $request)
    {
        $token   = SettingService::get('meta.access_token');
        $version = SettingService::get('meta.api_version', 'v21.0');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Access token non configuré.',
            ]);
        }

        try {
            $response = Http::timeout(10)
                ->get("https://graph.facebook.com/{$version}/me", [
                    'access_token' => $token,
                    'fields'       => 'id,name,email',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => 'Token valide ! Connecté en tant que : ' . ($data['name'] ?? $data['id']),
                    'data'    => $data,
                ]);
            }

            $error = $response->json('error.message', 'Erreur inconnue');
            return response()->json([
                'success' => false,
                'message' => 'Token invalide : ' . $error,
                'data'    => $response->json(),
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de joindre l\'API Meta : ' . $e->getMessage(),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────

    private function getGroup(string $group): array
    {
        $rows = \App\Models\Setting::where('group', $group)->pluck('value', 'key');

        // Fallback sur config pour les clés non encore sauvegardées en DB
        $defaults = [
            'n8n' => [
                'n8n.mock_mode'        => 'true',
                'n8n.webhook_create'   => config('services.n8n.webhook_create'),
                'n8n.webhook_activate' => config('services.n8n.webhook_activate'),
                'n8n.webhook_pause'    => config('services.n8n.webhook_pause'),
                'n8n.secret'           => config('services.n8n.secret'),
                'n8n.timeout'          => '10',
            ],
            'meta' => [
                'meta.mock_mode'    => 'true',
                'meta.app_id'       => config('services.meta.app_id'),
                'meta.app_secret'   => config('services.meta.app_secret'),
                'meta.access_token' => config('services.meta.access_token'),
                'meta.api_version'  => config('services.meta.api_version', 'v21.0'),
            ],
        ];

        return array_merge($defaults[$group] ?? [], $rows->toArray());
    }
}
