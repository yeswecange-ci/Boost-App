<?php

namespace App\Http\Controllers;

use App\Models\BoostCampaign;
use App\Models\FacebookPost;
use App\Models\SyncRun;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user        = auth()->user();
        $isValidator = $user->hasRole(['validator_n1', 'validator_n2', 'validator', 'admin']);
        $isN1        = $user->hasRole(['validator_n1', 'validator', 'admin']);
        $isN2        = $user->hasRole(['validator_n2', 'admin']);

        // Stats campagnes — filtrées par rôle (opérateurs voient les leurs, validateurs voient tout)
        $campBase = fn() => BoostCampaign::when(!$isValidator, fn($q) => $q->where('user_id', $user->id));

        $campaignCounts = [
            'total'      => $campBase()->count(),
            'draft'      => $campBase()->where('execution_status', 'draft')->count(),
            'pending_n1' => $campBase()->where('execution_status', 'pending_n1')->count(),
            'pending_n2' => $campBase()->where('execution_status', 'pending_n2')->count(),
            'approved'   => $campBase()->where('execution_status', 'approved')->count(),
            'done'       => $campBase()->where('execution_status', 'done')->count(),
            'error'      => $campBase()->whereIn('execution_status', ['error', 'rejected'])->count(),
        ];

        $recentCampaigns = $campBase()->with('user')->latest()->take(5)->get();

        // Monitoring sync (validateurs / admin uniquement)
        $lastSyncRun = $isValidator
            ? SyncRun::orderByDesc('started_at')->first()
            : null;

        $nonBoostableCount = $isValidator
            ? FacebookPost::where(function ($q) {
                $q->where('fb_status', '!=', 'FB_OK')
                  ->orWhere('business_status', '!=', 'ACTIVE')
                  ->orWhere('is_boostable', 0);
              })->count()
            : 0;

        return view('home', compact(
            'isValidator', 'isN1', 'isN2',
            'campaignCounts',
            'recentCampaigns',
            'lastSyncRun',
            'nonBoostableCount'
        ));
    }
}
