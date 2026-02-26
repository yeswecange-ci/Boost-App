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
            $posts = $this->metaService->getPagePosts($selectedPageId);
        }

        return view('posts.index', compact('pages', 'selectedPage', 'posts'));
    }
}