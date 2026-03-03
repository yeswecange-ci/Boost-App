<?php

namespace App\Http\Controllers;

use App\Models\SyncRun;
use App\Models\FacebookPost;

class SyncRunController extends Controller
{
    public function index()
    {
        $runs = SyncRun::withCount('errors')
            ->orderByDesc('started_at')
            ->paginate(20);

        $nonBoostableCount = FacebookPost::where(function ($q) {
            $q->where('fb_status', '!=', 'FB_OK')
              ->orWhere('business_status', '!=', 'ACTIVE')
              ->orWhere('is_boostable', 0);
        })->count();

        return view('sync-runs.index', compact('runs', 'nonBoostableCount'));
    }

    public function show(SyncRun $syncRun)
    {
        $syncRun->load(['errors', 'postHistories.postMaster']);

        $changedPosts = $syncRun->postHistories()
            ->with('postMaster')
            ->get()
            ->groupBy('post_master_id');

        return view('sync-runs.show', compact('syncRun', 'changedPosts'));
    }
}
