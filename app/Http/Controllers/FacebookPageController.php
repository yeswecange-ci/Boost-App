<?php

namespace App\Http\Controllers;

use App\Models\FacebookPage;
use App\Models\SyncRun;
use App\Services\MetaPostService;
use Illuminate\Http\Request;

class FacebookPageController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index()
    {
        $pages = FacebookPage::withCount('posts')->orderBy('page_name')->get();

        $lastSyncs = SyncRun::where('status', 'FINISHED')
            ->whereIn('page_id', $pages->pluck('page_id'))
            ->orderByDesc('finished_at')
            ->get()
            ->groupBy('page_id')
            ->map(fn($runs) => $runs->first());

        return view('admin.facebook-pages.index', compact('pages', 'lastSyncs'));
    }

    // ── Import depuis Meta (/me/accounts) ────────────────────────
    public function import()
    {
        $service = app(MetaPostService::class);
        $result  = $service->getUserPages();

        if ($result['error']) {
            return back()->with('error', 'Import Meta échoué : ' . $result['error']);
        }

        $count = 0;
        foreach ($result['data'] as $p) {
            FacebookPage::updateOrCreate(
                ['page_id' => $p['id']],
                [
                    'page_name'    => $p['name'],
                    'access_token' => $p['access_token'],
                    'is_active'    => true,
                ]
            );
            $count++;
        }

        return back()->with('success', "{$count} page(s) importée(s) / mises à jour depuis Meta.");
    }

    // ── Ajout manuel ─────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'page_id'              => 'required|string|max:50|unique:facebook_pages,page_id',
            'page_name'            => 'required|string|max:255',
            'access_token'         => 'required|string',
            'ad_account_id'        => 'nullable|string|max:100',
            'instagram_account_id' => 'nullable|string|max:100',
        ]);

        $page = FacebookPage::create($request->only([
            'page_id', 'page_name', 'access_token', 'ad_account_id', 'instagram_account_id',
        ]));

        return redirect()->route('admin.facebook-pages.index')
            ->with('success', "Page « {$page->page_name} » ajoutée.");
    }

    // ── Édition ──────────────────────────────────────────────────
    public function edit(FacebookPage $page)
    {
        return view('admin.facebook-pages.edit', compact('page'));
    }

    public function update(Request $request, FacebookPage $page)
    {
        $request->validate([
            'page_name'            => 'required|string|max:255',
            'access_token'         => 'required|string',
            'ad_account_id'        => 'nullable|string|max:100',
            'instagram_account_id' => 'nullable|string|max:100',
        ]);

        $page->update($request->only([
            'page_name', 'access_token', 'ad_account_id', 'instagram_account_id',
        ]));

        return redirect()->route('admin.facebook-pages.index')
            ->with('success', "Page « {$page->page_name} » mise à jour.");
    }

    // ── Activer / Désactiver ──────────────────────────────────────
    public function toggle(FacebookPage $page)
    {
        $page->update(['is_active' => !$page->is_active]);
        $label = $page->is_active ? 'activée' : 'désactivée';

        return back()->with('success', "Page « {$page->page_name} » {$label}.");
    }
}
