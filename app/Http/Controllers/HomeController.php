<?php

namespace App\Http\Controllers;

use App\Models\BoostCampaign;
use App\Models\FacebookPost;
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

        // Stats campagnes — filtrées par rôle et par pages assignées
        $pageIds        = $user->scopedFacebookPageIds(); // null = admin
        $allowedPostIds = $pageIds !== null
            ? FacebookPost::whereIn('facebook_page_id', $pageIds)->pluck('post_id')
            : null;

        $campBase = fn() => BoostCampaign::when(!$isValidator, fn($q) => $q->where('user_id', $user->id))
            ->when($allowedPostIds !== null, fn($q) => $q->whereIn('post_id', $allowedPostIds));

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

        return view('home', compact(
            'isValidator', 'isN1', 'isN2',
            'campaignCounts',
            'recentCampaigns'
        ));
    }
}
