<?php

namespace App\Services;

use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Models\PostHistory;
use App\Models\PostMediaHistory;
use App\Models\SyncRun;
use App\Models\SyncError;
use App\Services\SettingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaPostService
{
    private string $baseUrl;
    private string $accessToken;
    private bool   $mockMode;

    public function __construct()
    {
        $version           = SettingService::get('meta.api_version', 'v21.0');
        $this->baseUrl     = 'https://graph.facebook.com/' . $version;
        $this->accessToken = SettingService::get('meta.access_token', '');
        $this->mockMode    = SettingService::bool('meta.mock_mode', false);
    }

    // ─────────────────────────────────────────────────────────
    // Point d'entrée principal — crée le sync_run + SCD2
    // ─────────────────────────────────────────────────────────

    public function getPagePosts(string $pageId, int $limit = 12): array
    {
        // 1. Ouvrir un run d'audit
        $syncRun = SyncRun::create([
            'source'     => 'facebook',
            'page_id'    => $pageId,
            'status'     => 'RUNNING',
            'started_at' => now(),
        ]);

        try {
            $result = $this->mockMode
                ? $this->getMockPosts($pageId, $limit)
                : $this->fetchFromApi($pageId, $limit, $syncRun);

            if (empty($result['error']) && !empty($result['data'])) {
                $this->upsertPostsWithSCD2($pageId, $result['data'], $syncRun);
            }

            $syncRun->finish();
            return $result;

        } catch (\Throwable $e) {
            $syncRun->fail($e->getMessage());
            Log::error('MetaPostService::getPagePosts exception', [
                'page_id' => $pageId,
                'error'   => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    // ─────────────────────────────────────────────────────────
    // Appel API Graph (mode réel)
    // ─────────────────────────────────────────────────────────

    private function fetchFromApi(string $pageId, int $limit, SyncRun $syncRun): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/{$pageId}/posts", [
                'fields'       => 'id,message,story,created_time,full_picture,permalink_url,attachments,insights.metric(post_impressions)',
                'limit'        => $limit,
                'access_token' => $this->accessToken,
            ]);

            if ($response->failed()) {
                $errorBody = $response->json() ?? [];
                $errorCode = (string) ($errorBody['error']['code'] ?? $response->status());
                $fbtrace   = $errorBody['error']['fbtrace_id'] ?? null;

                SyncError::create([
                    'run_id'        => $syncRun->id,
                    'post_id'       => null,
                    'step'          => 'fetch_feed',
                    'error_code'    => $errorCode,
                    'error_message' => $errorBody['error']['message'] ?? 'HTTP ' . $response->status(),
                    'payload'       => ['fbtrace_id' => $fbtrace, 'body' => $errorBody],
                ]);

                Log::error('Meta API Error — fetch_feed', [
                    'page_id'    => $pageId,
                    'status'     => $response->status(),
                    'error_code' => $errorCode,
                ]);

                return ['error' => 'Impossible de récupérer les posts. Vérifiez le token.', 'data' => []];
            }

            $posts = $response->json('data', []);

            return [
                'error' => null,
                'data'  => array_map([$this, 'formatPost'], $posts),
            ];

        } catch (\Exception $e) {
            SyncError::create([
                'run_id'        => $syncRun->id,
                'post_id'       => null,
                'step'          => 'fetch_feed',
                'error_code'    => 'EXCEPTION',
                'error_message' => $e->getMessage(),
                'payload'       => null,
            ]);

            Log::error('MetaPostService fetchFromApi exception: ' . $e->getMessage());
            return ['error' => $e->getMessage(), 'data' => []];
        }
    }

    // ─────────────────────────────────────────────────────────
    // Vérification accessibilité d'un post individuel
    // Retourne le fb_status à mettre dans posts_master
    // ─────────────────────────────────────────────────────────

    private function checkPostAccessibility(string $postId, SyncRun $syncRun): string
    {
        if ($this->mockMode) {
            return 'FB_OK';
        }

        try {
            $response = Http::get("{$this->baseUrl}/{$postId}", [
                'fields'       => 'id',
                'access_token' => $this->accessToken,
            ]);

            if ($response->failed()) {
                $errorBody = $response->json() ?? [];
                $errorCode = (string) ($errorBody['error']['code'] ?? $response->status());

                // Codes #10, #100, #190 = supprimé ou non accessible
                $deletedCodes = ['10', '100', '190', '200', '368'];
                $status = in_array($errorCode, $deletedCodes)
                    ? 'FB_DELETED_OR_UNAVAILABLE'
                    : 'FB_ERROR';

                SyncError::create([
                    'run_id'        => $syncRun->id,
                    'post_id'       => $postId,
                    'step'          => 'check_post_access',
                    'error_code'    => $errorCode,
                    'error_message' => $errorBody['error']['message'] ?? 'HTTP ' . $response->status(),
                    'payload'       => ['fbtrace_id' => $errorBody['error']['fbtrace_id'] ?? null],
                ]);

                return $status;
            }

            return 'FB_OK';

        } catch (\Exception $e) {
            SyncError::create([
                'run_id'        => $syncRun->id,
                'post_id'       => $postId,
                'step'          => 'check_post_access',
                'error_code'    => 'EXCEPTION',
                'error_message' => $e->getMessage(),
                'payload'       => null,
            ]);
            return 'FB_ERROR';
        }
    }

    // ─────────────────────────────────────────────────────────
    // Upsert posts_master + SCD2 posts_history + post_media_history
    // ─────────────────────────────────────────────────────────

    private function upsertPostsWithSCD2(string $pageId, array $posts, SyncRun $syncRun): void
    {
        $page = FacebookPage::where('page_id', $pageId)->first();
        if (!$page) {
            return;
        }

        foreach ($posts as $post) {
            try {
                // Vérifier accessibilité (mode réel seulement, pour limiter les appels API en mock)
                $fbStatus = $this->checkPostAccessibility($post['id'], $syncRun);
                $isBoostable = ($fbStatus === 'FB_OK') ? 1 : 0;

                // 1. Upsert posts_master
                $master = FacebookPost::updateOrCreate(
                    ['post_id' => $post['id']],
                    [
                        'facebook_page_id'   => $page->id,
                        'message'            => $post['message'],
                        'thumbnail_url'      => $post['thumbnail'],
                        'permalink_url'      => $post['permalink_url'],
                        'type'               => $post['type'],
                        'impressions'        => $post['impressions'],
                        'posted_at'          => $post['created_time'],
                        'last_synced_at'     => now(),
                        'fb_status'          => $fbStatus,
                        'fb_last_checked_at' => now(),
                        'fb_last_error'      => null,
                        'business_status'    => 'ACTIVE',
                        'is_boostable'       => $isBoostable,
                        'last_sync_run_id'   => $syncRun->id,
                    ]
                );

                // Si le post n'est pas accessible, marquer fb_last_error
                if ($fbStatus !== 'FB_OK') {
                    $master->update(['fb_last_error' => "Statut : {$fbStatus} — vérifié lors du run #{$syncRun->id}"]);
                }

                // 2. SCD2 — posts_history
                $this->upsertPostHistory($master, $post, $syncRun);

                // 3. SCD2 — post_media_history (si attachements disponibles)
                if (!empty($post['attachments'])) {
                    $this->upsertMediaHistory($master, $post['attachments'], $syncRun);
                }

            } catch (\Exception $e) {
                SyncError::create([
                    'run_id'        => $syncRun->id,
                    'post_id'       => $post['id'] ?? null,
                    'step'          => 'upsert_post',
                    'error_code'    => 'EXCEPTION',
                    'error_message' => $e->getMessage(),
                    'payload'       => null,
                ]);
                Log::error('MetaPostService::upsertPostsWithSCD2 — post error', [
                    'post_id' => $post['id'] ?? null,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────
    // SCD2 — posts_history
    // ─────────────────────────────────────────────────────────

    private function upsertPostHistory(FacebookPost $master, array $post, SyncRun $syncRun): void
    {
        // Calculer le hash sur les champs significatifs du post
        $hashInput = implode('|', [
            $post['message']       ?? '',
            $post['permalink_url'] ?? '',
            $post['thumbnail']     ?? '',
            $post['type']          ?? '',
            $post['link_url']      ?? '',
        ]);
        $rowHash = hash('sha256', $hashInput);

        // Vérifier si la version active a le même hash
        $currentVersion = PostHistory::where('post_master_id', $master->id)
            ->where('is_active', 1)
            ->first();

        if ($currentVersion && $currentVersion->row_hash === $rowHash) {
            return; // Aucun changement — on ne crée pas de nouvelle version
        }

        // Fermer la version précédente
        if ($currentVersion) {
            $currentVersion->update([
                'is_active' => 0,
                'valid_to'  => now(),
            ]);
        }

        // Insérer la nouvelle version active
        PostHistory::create([
            'post_master_id' => $master->id,
            'type'           => $post['type'],
            'message'        => $post['message'],
            'permalink_url'  => $post['permalink_url'],
            'created_time'   => $post['created_time'],
            'full_picture'   => $post['thumbnail'],
            'link_url'       => $post['link_url'] ?? null,
            'payload'        => $post['raw_payload'] ?? null,
            'row_hash'       => $rowHash,
            'run_id'         => $syncRun->id,
            'is_active'      => 1,
            'valid_from'     => now(),
            'valid_to'       => null,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // SCD2 — post_media_history
    // ─────────────────────────────────────────────────────────

    private function upsertMediaHistory(FacebookPost $master, array $attachments, SyncRun $syncRun): void
    {
        // Construire le snapshot courant des médias
        $currentMedia = [];
        foreach ($attachments as $position => $attachment) {
            $mediaType = match (true) {
                str_contains($attachment['type'] ?? '', 'video') => 'video',
                str_contains($attachment['type'] ?? '', 'photo') => 'image',
                default                                          => 'unknown',
            };

            $currentMedia[] = [
                'position'   => $position + 1,
                'media_type' => $mediaType,
                'source_url' => $attachment['media']['image']['src'] ?? $attachment['url'] ?? null,
                'preview_url'=> $attachment['media']['image']['src'] ?? null,
                'link_url'   => $attachment['url'] ?? null,
                'title'      => $attachment['title'] ?? null,
                'payload'    => $attachment,
            ];
        }

        // Calculer le hash global des médias
        $hashInput = implode('||', array_map(fn($m) => implode('|', [
            $m['position'], $m['media_type'], $m['source_url'] ?? '', $m['link_url'] ?? '', $m['title'] ?? '',
        ]), $currentMedia));
        $globalHash = hash('sha256', $hashInput);

        // Vérifier si les médias actifs ont changé
        $activeHashes = PostMediaHistory::where('post_master_id', $master->id)
            ->where('is_active', 1)
            ->pluck('row_hash')
            ->sort()
            ->values()
            ->toArray();

        $newHashes = array_map(fn($m) => hash('sha256', implode('|', [
            $m['position'], $m['media_type'], $m['source_url'] ?? '', $m['link_url'] ?? '', $m['title'] ?? '',
        ])), $currentMedia);
        sort($newHashes);

        if ($activeHashes === $newHashes) {
            return; // Médias inchangés
        }

        // Fermer tous les médias actifs
        PostMediaHistory::where('post_master_id', $master->id)
            ->where('is_active', 1)
            ->update(['is_active' => 0, 'valid_to' => now()]);

        // Insérer les nouveaux médias
        foreach ($currentMedia as $media) {
            $rowHash = hash('sha256', implode('|', [
                $media['position'], $media['media_type'],
                $media['source_url'] ?? '', $media['link_url'] ?? '', $media['title'] ?? '',
            ]));

            PostMediaHistory::create([
                'post_master_id' => $master->id,
                'position'       => $media['position'],
                'media_type'     => $media['media_type'],
                'source_url'     => $media['source_url'],
                'preview_url'    => $media['preview_url'],
                'link_url'       => $media['link_url'],
                'title'          => $media['title'],
                'payload'        => $media['payload'],
                'row_hash'       => $rowHash,
                'run_id'         => $syncRun->id,
                'is_active'      => 1,
                'valid_from'     => now(),
                'valid_to'       => null,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────
    // Pages gérées par l'utilisateur
    // ─────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────
    // Formatage d'un post brut API → tableau normalisé
    // ─────────────────────────────────────────────────────────

    private function formatPost(array $post): array
    {
        $attachments = $post['attachments']['data'] ?? [];

        return [
            'id'            => $post['id'],
            'message'       => $post['message'] ?? $post['story'] ?? 'Post sans texte',
            'created_time'  => $post['created_time'] ?? null,
            'thumbnail'     => $post['full_picture'] ?? null,
            'permalink_url' => $post['permalink_url'] ?? '#',
            'type'          => $this->detectPostType($post),
            'impressions'   => $post['insights']['data'][0]['values'][0]['value'] ?? 0,
            'link_url'      => $post['link'] ?? null,
            'attachments'   => $attachments,
            'raw_payload'   => [
                'id'           => $post['id'],
                'created_time' => $post['created_time'] ?? null,
                'type'         => $post['type'] ?? null,
            ],
        ];
    }

    private function detectPostType(array $post): string
    {
        if (!empty($post['attachments']['data'])) {
            $type = $post['attachments']['data'][0]['type'] ?? '';
            return match(true) {
                str_contains($type, 'video') => 'video',
                str_contains($type, 'photo') => 'photo',
                str_contains($type, 'share') => 'link',
                default                      => 'status',
            };
        }
        return 'status';
    }

    // ─────────────────────────────────────────────────────────
    // MOCK DATA — développement sans token Meta
    // ─────────────────────────────────────────────────────────

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
            $date     = now()->subDays($i * 3)->toIso8601String();
            $postId   = $pageId . '_' . (1000000 + $i);
            $postType = $types[$i % count($types)];

            $posts[] = [
                'id'            => $postId,
                'message'       => $messages[$i % count($messages)],
                'created_time'  => $date,
                'thumbnail'     => "https://picsum.photos/seed/{$pageId}{$i}/600/400",
                'permalink_url' => "https://facebook.com/{$pageId}/posts/" . (1000000 + $i),
                'type'          => $postType,
                'impressions'   => rand(500, 50000),
                'link_url'      => null,
                'attachments'   => $postType === 'photo' ? [[
                    'type'  => 'photo',
                    'media' => ['image' => ['src' => "https://picsum.photos/seed/{$pageId}{$i}/600/400"]],
                    'url'   => "https://facebook.com/{$pageId}/posts/" . (1000000 + $i),
                    'title' => null,
                ]] : [],
                'raw_payload'   => ['id' => $postId, 'created_time' => $date, 'type' => $postType],
            ];
        }

        return ['error' => null, 'data' => $posts];
    }

    private function getMockPages(): array
    {
        return [
            'error' => null,
            'data'  => [
                ['id' => '123456789', 'name' => 'Bracongo CI',      'access_token' => 'mock_token_1'],
                ['id' => '987654321', 'name' => 'Orange CI',        'access_token' => 'mock_token_2'],
                ['id' => '111222333', 'name' => 'Mercedes-Benz CI', 'access_token' => 'mock_token_3'],
            ]
        ];
    }
}
