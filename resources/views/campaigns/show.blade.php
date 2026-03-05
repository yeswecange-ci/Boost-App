@extends('layouts.app')

@section('page-title', 'Campagne #' . $campaign->id)
@section('page-subtitle', $campaign->campaign_name)

@section('content')

<div style="max-width:860px; margin:0 auto; display:flex; flex-direction:column; gap:1.5rem;">

    {{-- Statut + IDs Meta --}}
    <div class="card">
        <div class="card-header" style="justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:0.5rem;">
                <i class="fas fa-layer-group" style="color:var(--color-primary);"></i>
                Statut d'exécution
            </div>
            <span class="badge-status {{ $campaign->status_class }}">{{ $campaign->status_label }}</span>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem;">
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Créée le</div>
                    <div style="font-weight:600;">{{ $campaign->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Lancée le</div>
                    <div style="font-weight:600;">{{ $campaign->launched_at?->format('d/m/Y H:i') ?? '—' }}</div>
                </div>
                @if($campaign->meta_campaign_id)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Campaign ID Meta</div>
                    <div style="font-family:monospace; font-size:0.875rem; font-weight:600; color:var(--color-primary);">{{ $campaign->meta_campaign_id }}</div>
                </div>
                @endif
                @if($campaign->meta_adset_id)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">AdSet ID Meta</div>
                    <div style="font-family:monospace; font-size:0.875rem; font-weight:600; color:var(--color-primary);">{{ $campaign->meta_adset_id }}</div>
                </div>
                @endif
                @if($campaign->meta_ad_id)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Ad ID Meta</div>
                    <div style="font-family:monospace; font-size:0.875rem; font-weight:600; color:var(--color-primary);">{{ $campaign->meta_ad_id }}</div>
                </div>
                @endif
            </div>
            @if($campaign->error_message)
            <div class="alert alert-danger" style="margin-top:1rem;">
                <i class="fas fa-exclamation-triangle"></i> {{ $campaign->error_message }}
            </div>
            @endif
        </div>
    </div>

    {{-- Détails campagne --}}
    <div class="card">
        <div class="card-header">
            <i class="fas fa-bullhorn" style="color:var(--color-primary);"></i>
            Campagne
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                @foreach([
                    'Nom'              => $campaign->campaign_name,
                    'Objectif'         => $campaign->campaign_objective,
                    'Catégorie spéc.'  => $campaign->special_ad_categories,
                    'Statut initial'   => $campaign->campaign_status,
                    'Campagne existante' => $campaign->existing_campaign_id ?? '—',
                    'Post ID'          => $campaign->post_id,
                ] as $label => $val)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">{{ $label }}</div>
                    <div style="font-weight:500; font-family:{{ in_array($label, ['Post ID','Campagne existante']) ? 'monospace' : 'inherit' }};">{{ $val }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Ad Set --}}
    <div class="card">
        <div class="card-header">
            <i class="fas fa-crosshairs" style="color:var(--color-primary);"></i>
            Ad Set
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                @foreach([
                    'Nom Ad Set'       => $campaign->adset_name,
                    'Budget'           => $campaign->budget_formatted . ' (' . $campaign->budget_type . ')',
                    'Durée'            => $campaign->duration_days . ' jours',
                    'Optimization'     => $campaign->optimization_goal,
                    'Billing Event'    => $campaign->billing_event,
                    'Bid Strategy'     => $campaign->bid_strategy,
                ] as $label => $val)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">{{ $label }}</div>
                    <div style="font-weight:500;">{{ $val }}</div>
                </div>
                @endforeach
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Pays</div>
                    <div style="display:flex; gap:0.25rem; flex-wrap:wrap;">
                        @foreach($campaign->countries as $c)
                        <span class="badge-status badge-status-draft">{{ $c }}</span>
                        @endforeach
                    </div>
                </div>
                @if($campaign->interests)
                <div>
                    <div style="font-size:0.75rem; color:var(--color-muted); text-transform:uppercase; letter-spacing:.05em; margin-bottom:0.25rem;">Intérêts</div>
                    <div style="font-size:0.8125rem; color:var(--color-muted);">{{ count($campaign->interests) }} sélectionné(s)</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card-footer" style="background:transparent; border:none; padding:0;">
        <a href="{{ route('campaigns.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour aux campagnes
        </a>
    </div>

</div>

@endsection
