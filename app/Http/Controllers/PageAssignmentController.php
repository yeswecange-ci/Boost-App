<?php

namespace App\Http\Controllers;

use App\Models\FacebookPage;
use App\Models\SyncRun;
use App\Models\User;
use App\Services\MetaPostService;
use Illuminate\Http\Request;

class PageAssignmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index()
    {
        $users = User::with(['roles', 'facebookPages'])
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))
            ->orderBy('name')
            ->get();

        $pages = FacebookPage::where('is_active', true)->orderBy('page_name')->get();

        // Dernière sync réussie par page (page_id externe)
        $lastSyncs = SyncRun::where('status', 'FINISHED')
            ->whereIn('page_id', $pages->pluck('page_id'))
            ->orderByDesc('finished_at')
            ->get()
            ->groupBy('page_id')
            ->map(fn($runs) => $runs->first()); // la plus récente par page

        return view('admin.page-assignments', compact('users', 'pages', 'lastSyncs'));
    }

    public function syncPosts(FacebookPage $page)
    {
        $service = app(MetaPostService::class);
        $result  = $service->getPagePosts($page->page_id, limit: 50);

        if ($result['error']) {
            return back()->with('error', "Sync « {$page->page_name} » échouée : {$result['error']}");
        }

        $count = count($result['data']);
        return back()->with('success', "{$count} post(s) synchronisé(s) depuis « {$page->page_name} ».");
    }

    public function update(Request $request)
    {
        $request->validate([
            'assignments'   => 'nullable|array',
            'assignments.*' => 'nullable|array',
        ]);

        $users = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))->get();

        foreach ($users as $user) {
            $pageIds = $request->input("assignments.{$user->id}", []);
            $user->facebookPages()->sync($pageIds);
        }

        return back()->with('success', 'Assignations de pages sauvegardées.');
    }
}
