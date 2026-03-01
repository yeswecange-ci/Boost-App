<?php

namespace App\Http\Controllers;

use App\Models\BoostRequest;
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

        return view('home', compact(
            'isValidator',
            'totalBoosts',
            'pendingCount',
            'activeCount',
            'budgetByCurrency',
            'recentBoosts'
        ));
    }
}
