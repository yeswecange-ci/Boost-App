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

    @php
        $user        = auth()->user();
        $isValidator = $user->hasRole(['validator_n1','validator_n2','validator','admin']);
        $isOperator  = $user->hasRole(['operator','admin']);
        $isAdmin     = $user->hasRole('admin');
        $status      = $campaign->execution_status;
    @endphp

    {{-- Motif de rejet --}}
    @if($status === 'rejected' && $campaign->error_message)
    <div class="alert alert-danger">
        <strong><i class="fas fa-times-circle"></i> Campagne rejetée</strong>
        <p style="margin:.375rem 0 0; font-size:.875rem;">{{ $campaign->error_message }}</p>
    </div>
    @endif

    {{-- Barre d'actions --}}
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.75rem;">
        <a href="{{ route('campaigns.index') }}" class="btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour aux campagnes
        </a>

        <div style="display:flex; gap:0.75rem; flex-wrap:wrap;" x-data="{ rejectOpen: false, reason: '' }">

            {{-- Opérateur : soumettre pour validation --}}
            @if($isOperator && in_array($status, ['draft','rejected']))
            <form method="POST" action="{{ route('campaigns.submit', $campaign->id) }}">
                @csrf
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Soumettre pour validation
                </button>
            </form>
            @endif

            {{-- Validateur : approuver --}}
            @if($isValidator && $status === 'pending')
            <form method="POST" action="{{ route('campaigns.approve', $campaign->id) }}">
                @csrf
                <button type="submit" class="btn-success">
                    <i class="fas fa-check"></i> Approuver
                </button>
            </form>
            @endif

            {{-- Validateur : rejeter (modal Alpine) --}}
            @if($isValidator && $status === 'pending')
            <button type="button" class="btn-danger" @click="rejectOpen = true">
                <i class="fas fa-times"></i> Rejeter
            </button>

            <div x-show="rejectOpen" x-transition
                 style="position:fixed; inset:0; background:rgba(15,23,42,.5); z-index:50; display:flex; align-items:center; justify-content:center; padding:1rem;">
                <div style="background:#fff; border-radius:0.875rem; padding:1.5rem; max-width:480px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.2);" @click.stop>
                    <h3 style="font-size:1rem; font-weight:700; margin:0 0 .25rem;">Rejeter la campagne</h3>
                    <p style="font-size:.875rem; color:var(--color-muted); margin:0 0 1rem;">L'opérateur recevra ce motif et pourra corriger sa campagne.</p>
                    <form method="POST" action="{{ route('campaigns.reject', $campaign->id) }}">
                        @csrf
                        <textarea name="reason" x-model="reason" required
                                  placeholder="Expliquez pourquoi cette campagne est rejetée…"
                                  style="width:100%; padding:.75rem; border:1.5px solid var(--color-border); border-radius:.5rem; font-size:.875rem; min-height:100px; outline:none; resize:vertical;"
                                  onfocus="this.style.borderColor='var(--color-primary)'"
                                  onblur="this.style.borderColor='var(--color-border)'"></textarea>
                        <div style="display:flex; gap:.75rem; justify-content:flex-end; margin-top:1rem;">
                            <button type="button" class="btn-secondary" @click="rejectOpen = false">Annuler</button>
                            <button type="submit" class="btn-danger" :disabled="!reason.trim()">
                                <i class="fas fa-times"></i> Confirmer le rejet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif

            {{-- Opérateur/Admin : booster (seulement si approuvé) --}}
            @if($isOperator && $status === 'approved')
            <form method="POST" action="{{ route('campaigns.launch', $campaign->id) }}"
                  onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Lancement…';">
                @csrf
                <button type="submit" class="btn-primary">
                    <i class="fas fa-rocket"></i> Booster ce post
                </button>
            </form>
            @endif

            {{-- Admin : bypass validation —lancement direct depuis draft/error --}}
            @if($isAdmin && in_array($status, ['draft','error']))
            <form method="POST" action="{{ route('campaigns.launch', $campaign->id) }}"
                  onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Lancement…';">
                @csrf
                <button type="submit" class="btn-secondary">
                    <i class="fas fa-rocket"></i> {{ $status === 'error' ? 'Relancer' : 'Lancer sans validation' }}
                </button>
            </form>
            @endif

        </div>
    </div>

</div>

@endsection
