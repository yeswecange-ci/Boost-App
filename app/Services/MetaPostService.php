<?php

namespace App\Services;

use App\Services\SettingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaPostService
{
    private string $baseUrl;
    private string $accessToken;
    private bool $mockMode;

    public function __construct()
    {
        $version           = SettingService::get('meta.api_version', 'v21.0');
        $this->baseUrl     = 'https://graph.facebook.com/' . $version;
        $this->accessToken = SettingService::get('meta.access_token', '');
        $this->mockMode    = SettingService::bool('meta.mock_mode', true);
    }

    /**
     * Récupère les posts d'une page Facebook
     */
    public function getPagePosts(string $pageId, int $limit = 12): array
    {
        if ($this->mockMode) {
            return $this->getMockPosts($pageId, $limit);
        }

        try {
            $response = Http::get("{$this->baseUrl}/{$pageId}/posts", [
                'fields'       => 'id,message,story,created_time,full_picture,permalink_url,attachments,insights.metric(post_impressions)',
                'limit'        => $limit,
                'access_token' => $this->accessToken,
            ]);

            if ($response->failed()) {
                Log::error('Meta API Error', [
                    'page_id' => $pageId,
                    'status'  => $response->status(),
                    'body'    => $response->body(),
                ]);
                return ['error' => 'Impossible de récupérer les posts. Vérifiez le token.', 'data' => []];
            }

            $posts = $response->json('data', []);

            return [
                'error' => null,
                'data'  => array_map([$this, 'formatPost'], $posts),
            ];

        } catch (\Exception $e) {
            Log::error('MetaPostService Exception: ' . $e->getMessage());
            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Récupère les pages gérées par l'utilisateur (avec user token)
     */
    public function getUserPages(): array
    {
        if ($this->mockMode) {
            return $this->getMockPages();
        }

        try {
            $response = Http::get("{$this->baseUrl}/me/accounts", [
                'fields'       => 'id,name,access_token,picture',
                'access_token' => $this->accessToken,
            ]);

            if ($response->failed()) {
                return ['error' => 'Impossible de récupérer les pages.', 'data' => []];
            }

            return ['error' => null, 'data' => $response->json('data', [])];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * Formate un post pour l'affichage
     */
    private function formatPost(array $post): array
    {
        return [
            'id'            => $post['id'],
            'message'       => $post['message'] ?? $post['story'] ?? 'Post sans texte',
            'created_time'  => $post['created_time'] ?? null,
            'thumbnail'     => $post['full_picture'] ?? null,
            'permalink_url' => $post['permalink_url'] ?? '#',
            'type'          => $this->detectPostType($post),
            'impressions'   => $post['insights']['data'][0]['values'][0]['value'] ?? 0,
        ];
    }

    /**
     * Détecte le type de post
     */
    private function detectPostType(array $post): string
    {
        if (!empty($post['attachments']['data'])) {
            $type = $post['attachments']['data'][0]['type'] ?? '';
            return match(true) {
                str_contains($type, 'video') => 'video',
                str_contains($type, 'photo') => 'photo',
                str_contains($type, 'share') => 'link',
                default => 'status',
            };
        }
        return 'status';
    }

    // =========================================================
    // MOCK DATA — pour développer sans token Meta
    // =========================================================

    private function getMockPosts(string $pageId, int $limit): array
    {
        $posts = [];
        $types = ['photo', 'video', 'link', 'status'];
        $messages = [
            '🎉 Grande nouvelle ! Notre nouveau produit est enfin disponible. Découvrez-le maintenant !',
            '⚽ CAN 2025 — La Côte d\'Ivoire se qualifie pour les demi-finales ! Félicitations aux Éléphants !',
            '🎶 Ambiance garantie à notre prochain événement. Réservez vos places dès maintenant.',
            '🌟 Merci à tous nos clients pour votre confiance. Nous continuons à vous servir avec passion.',
            '🔥 Promotion exceptionnelle ce weekend seulement ! Ne manquez pas cette opportunité.',
            '📢 Nouveau partenariat annoncé ! Nous sommes fiers de collaborer avec des partenaires de renom.',
            '💡 Innovation et excellence — nos valeurs au service de votre satisfaction.',
            '🎁 Participez à notre concours et gagnez de super lots ! Likez et partagez ce post.',
            '📱 Notre application mobile est maintenant disponible sur App Store et Google Play.',
            '🏆 Nous sommes fiers de remporter le prix de la meilleure entreprise de l\'année !',
            '🌍 Expansion en Afrique de l\'Ouest — nous ouvrons 3 nouvelles agences ce mois-ci.',
            '☕ Commencez bien votre journée avec nos nouveaux produits matinaux !',
        ];

        for ($i = 0; $i < min($limit, 12); $i++) {
            $date = now()->subDays($i * 3)->toIso8601String();
            $posts[] = [
                'id'            => $pageId . '_' . (1000000 + $i),
                'message'       => $messages[$i % count($messages)],
                'created_time'  => $date,
                'thumbnail'     => "https://picsum.photos/seed/{$pageId}{$i}/600/400",
                'permalink_url' => "https://facebook.com/{$pageId}/posts/" . (1000000 + $i),
                'type'          => $types[$i % count($types)],
                'impressions'   => rand(500, 50000),
            ];
        }

        return ['error' => null, 'data' => $posts];
    }

    private function getMockPages(): array
    {
        return [
            'error' => null,
            'data'  => [
                ['id' => '123456789', 'name' => 'Bracongo CI',     'access_token' => 'mock_token_1'],
                ['id' => '987654321', 'name' => 'Orange CI',       'access_token' => 'mock_token_2'],
                ['id' => '111222333', 'name' => 'Mercedes-Benz CI','access_token' => 'mock_token_3'],
            ]
        ];
    }
}