<?php

namespace App\Http\Controllers;

use App\Models\FacebookPage;
use App\Services\MetaPostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private MetaPostService $metaService) {}

    public function index(Request $request)
    {
        // Récupère les pages disponibles pour cet utilisateur
        $user  = auth()->user();
        $pages = FacebookPage::where('is_active', true)->get();

        // Si admin ou validator → toutes les pages
        // Si operator → seulement ses pages assignées
        if ($user->hasRole('operator') && !empty($user->page_ids)) {
            $pages = $pages->whereIn('page_id', $user->page_ids);
        }

        // Page sélectionnée (par défaut la première)
        $selectedPageId = $request->get('page_id', $pages->first()?->page_id);
        $selectedPage   = $pages->firstWhere('page_id', $selectedPageId);

        $posts = ['data' => [], 'error' => null];

        if ($selectedPage) {
            // Fetch depuis l'API (ou mock) + upsert en BD
            $this->metaService->getPagePosts($selectedPageId);

            // Lire depuis la BD (données persistées, stables)
            $dbPosts = \App\Models\FacebookPost::where('facebook_page_id', $selectedPage->id)
                ->orderByDesc('posted_at')
                ->get()
                ->map(fn($p) => [
                    'id'              => $p->post_id,
                    'message'         => $p->message,
                    'created_time'    => $p->posted_at?->toIso8601String(),
                    'thumbnail'       => $p->thumbnail_url,
                    'permalink_url'   => $p->permalink_url,
                    'type'            => $p->type,
                    'impressions'     => $p->impressions,
                    'is_boostable'    => $p->isBoostable(),
                    'fb_status'       => $p->fb_status ?? 'FB_OK',
                    'business_status' => $p->business_status ?? 'ACTIVE',
                    'boost_reason'    => $p->boostability_reason,
                ])->toArray();

            $posts = ['error' => null, 'data' => $dbPosts];
        }

        return view('posts.index', compact('pages', 'selectedPage', 'posts'));
    }
}