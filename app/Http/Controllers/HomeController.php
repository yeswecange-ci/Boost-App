<?php

namespace App\Http\Controllers;

use App\Models\BoostRequest;
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
        $user      = auth()->user();
        $isValidator = $user->hasRole(['validator_n1', 'validator_n2', 'validator', 'admin']);

        $baseQuery = fn() => BoostRequest::when(!$isValidator, fn($q) => $q->where('operator_id', $user->id));

        $totalBoosts  = $baseQuery()->count();
        $pendingCount = $baseQuery()->whereIn('status', ['pending_n1', 'pending_n2'])->count();
        $activeCount  = $baseQuery()->where('status', 'active')->count();

        // Budget regroupé par devise pour éviter de mélanger XOF/EUR/USD
        $budgetByCurrency = $baseQuery()
            ->whereIn('status', ['approved', 'paused_ready', 'active', 'completed'])
            ->selectRaw('currency, SUM(budget) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $recentBoosts = $baseQuery()
            ->with('operator')
            ->latest()
            ->take(5)
            ->get();

        // Stats de synchronisation (visibles pour les validateurs / admin)
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
            'isValidator',
            'totalBoosts',
            'pendingCount',
            'activeCount',
            'budgetByCurrency',
            'recentBoosts',
            'lastSyncRun',
            'nonBoostableCount'
        ));
    }
}
