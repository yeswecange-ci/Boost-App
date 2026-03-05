@extends('layouts.app')

@section('page-title', 'Dashboard')
@php
    $_role = auth()->user()->roles->first()?->name ?? '';
    $_subtitle = match(true) {
        in_array($_role, ['validator_n1','validator']) => 'Files de validation — Campagnes Media Buyer',
        $_role === 'validator_n2'                      => 'Validation finale N+2 — Campagnes Media Buyer',
        $_role === 'admin'                             => 'Vue administrative globale',
        default                                        => 'Vue d\'ensemble de vos campagnes',
    };
@endphp
@section('page-subtitle', $_subtitle)

@section('content')

{{-- Alertes session --}}
@if(session('success'))
<div class="alert alert-success" style="margin-bottom:1.25rem;">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="alert alert-danger" style="margin-bottom:1.25rem;">
    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
</div>
@endif

{{-- KPI Cards --}}
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1rem; margin-bottom:1.5rem;">

    <div class="stat-card">
        <div class="stat-icon" style="background:#eef2ff; color:#4f46e5;">
            <i class="fas fa-layer-group"></i>
        </div>
        <div>
            <div class="stat-value">{{ $campaignCounts['total'] }}</div>
            <div class="stat-label">{{ $isValidator ? 'Total campagnes' : 'Mes campagnes' }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3; color:#854d0e;">
            <i class="fas fa-clock"></i>
        </div>
        <div>
            <div class="stat-value">{{ $campaignCounts['pending_n1'] + $campaignCounts['pending_n2'] }}</div>
            <div class="stat-label">{{ $isValidator ? 'À valider' : 'En validation' }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#e0f2fe; color:#0369a1;">
            <i class="fas fa-check"></i>
        </div>
        <div>
            <div class="stat-value">{{ $campaignCounts['approved'] }}</div>
            <div class="stat-label">Approuvées</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7; color:#15803d;">
            <i class="fas fa-rocket"></i>
        </div>
        <div>
            <div class="stat-value">{{ $campaignCounts['done'] }}</div>
            <div class="stat-label">Boostées</div>
        </div>
    </div>

    @if($campaignCounts['error'] > 0)
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2; color:#b91c1c;">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div>
            <div class="stat-value" style="color:#b91c1c;">{{ $campaignCounts['error'] }}</div>
            <div class="stat-label">Erreurs</div>
        </div>
    </div>
    @endif

</div>

{{-- CTAs validation pour N+1 / N+2 --}}
@if($isN1 || $isN2)
<div style="display:flex; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">

    @if($isN1 && $campaignCounts['pending_n1'] > 0)
    <a href="{{ route('campaigns.pending') }}"
       style="display:flex; align-items:center; gap:.875rem; padding:1rem 1.25rem; background:#fef9c3; border:1.5px solid #fde68a; border-radius:.625rem; text-decoration:none; flex:1; min-width:220px;">
        <i class="fas fa-clock" style="font-size:1.5rem; color:#92400e;"></i>
        <div>
            <div style="font-size:1.75rem; font-weight:800; color:#92400e; line-height:1;">{{ $campaignCounts['pending_n1'] }}</div>
            <div style="font-size:.75rem; color:#92400e; font-weight:500; margin-top:.125rem;">Campagne(s) en attente N+1</div>
        </div>
        <i class="fas fa-arrow-right" style="margin-left:auto; color:#92400e;"></i>
    </a>
    @endif

    @if($isN2 && $campaignCounts['pending_n2'] > 0)
    <a href="{{ route('campaigns.pending') }}"
       style="display:flex; align-items:center; gap:.875rem; padding:1rem 1.25rem; background:#dbeafe; border:1.5px solid #93c5fd; border-radius:.625rem; text-decoration:none; flex:1; min-width:220px;">
        <i class="fas fa-check-circle" style="font-size:1.5rem; color:#1d4ed8;"></i>
        <div>
            <div style="font-size:1.75rem; font-weight:800; color:#1d4ed8; line-height:1;">{{ $campaignCounts['pending_n2'] }}</div>
            <div style="font-size:.75rem; color:#1d4ed8; font-weight:500; margin-top:.125rem;">Campagne(s) en attente N+2</div>
        </div>
        <i class="fas fa-arrow-right" style="margin-left:auto; color:#1d4ed8;"></i>
    </a>
    @endif

    @if((!$isN1 || $campaignCounts['pending_n1'] === 0) && (!$isN2 || $campaignCounts['pending_n2'] === 0))
    <div style="display:flex; align-items:center; gap:.625rem; color:var(--color-muted); font-size:.875rem; padding:.875rem 1rem; background:#f8fafc; border-radius:.625rem; flex:1;">
        <i class="fas fa-check-circle" style="color:#86efac; font-size:1.25rem;"></i>
        Aucune campagne en attente de validation. Tout est à jour !
    </div>
    @endif

</div>
@endif

{{-- Monitoring synchro (validateurs / admin uniquement) --}}
@if($isValidator && $lastSyncRun)
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header" style="justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-sync-alt" style="color:var(--color-primary);"></i>
            Dernière synchronisation Facebook
        </div>
        <a href="{{ route('sync-runs.index') }}" class="btn-secondary btn-sm">
            <i class="fas fa-list"></i> Voir tous les runs
        </a>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:1rem;">
            <div style="text-align:center;">
                @php
                $syncStyle = match($lastSyncRun->status) {
                    'FINISHED' => 'color:#15803d;',
                    'FAILED'   => 'color:#b91c1c;',
                    default    => 'color:#854d0e;',
                };
                $syncIcon = match($lastSyncRun->status) {
                    'FINISHED' => 'check-circle',
                    'FAILED'   => 'times-circle',
                    default    => 'spinner fa-spin',
                };
                @endphp
                <div style="font-size:1.5rem; {{ $syncStyle }}"><i class="fas fa-{{ $syncIcon }}"></i></div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">Statut</div>
                <div style="font-weight:600; font-size:0.875rem; {{ $syncStyle }}">{{ $lastSyncRun->status }}</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.25rem; color:#4f46e5; font-weight:700;">{{ $lastSyncRun->page_id }}</div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">Page ID</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.25rem; color:#4f46e5; font-weight:700;">
                    {{ $lastSyncRun->started_at->diffForHumans() }}
                </div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">Démarré</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.25rem; font-weight:700; {{ $nonBoostableCount > 0 ? 'color:#b91c1c;' : 'color:#15803d;' }}">
                    {{ $nonBoostableCount }}
                </div>
                <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">Posts non boostables</div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Dernières campagnes --}}
<div class="card">
    <div class="card-header" style="justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-history" style="color:var(--color-primary);"></i>
            Dernières campagnes
        </div>
        @if(!$isValidator)
        <a href="{{ route('campaigns.create') }}" class="btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nouvelle campagne
        </a>
        @endif
    </div>

    @if($recentCampaigns->isEmpty())
    <div class="card-body" style="text-align:center; padding:3rem 1.25rem; color:#64748b;">
        <i class="fas fa-layer-group" style="font-size:2.5rem; color:#e2e8f0; display:block; margin-bottom:1rem;"></i>
        <div style="font-weight:500; margin-bottom:0.375rem;">Aucune campagne pour l'instant</div>
        <div style="font-size:0.875rem;">
            @if(!$isValidator)
            Créez votre première campagne Media Buyer.
            @else
            Aucune campagne n'a encore été soumise.
            @endif
        </div>
        @if(!$isValidator)
        <a href="{{ route('campaigns.create') }}" class="btn-primary" style="display:inline-flex; margin-top:1rem;">
            <i class="fas fa-plus"></i> Créer une campagne
        </a>
        @endif
    </div>
    @else
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Campagne</th>
                    @if($isValidator)<th>Opérateur</th>@endif
                    <th>Budget</th>
                    <th>Durée</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentCampaigns as $c)
                <tr>
                    <td style="color:#94a3b8; font-size:0.8125rem;">#{{ $c->id }}</td>
                    <td>
                        <div style="font-weight:500; font-size:0.875rem;">{{ $c->campaign_name }}</div>
                        <div style="font-size:0.75rem; color:#64748b; font-family:monospace;">{{ $c->post_id }}</div>
                    </td>
                    @if($isValidator)
                    <td style="font-size:0.875rem; color:#374151;">{{ $c->user?->name ?? '(supprimé)' }}</td>
                    @endif
                    <td style="font-weight:600; color:var(--color-primary); font-size:0.875rem;">
                        {{ $c->budget_formatted }}
                    </td>
                    <td style="font-size:0.875rem;">{{ $c->duration_days }}j</td>
                    <td>
                        <span class="badge-status {{ $c->status_class }}">{{ $c->status_label }}</span>
                    </td>
                    <td>
                        <a href="{{ route('campaigns.show', $c->id) }}" class="btn-secondary btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer" style="display:flex; justify-content:flex-end;">
        @if($isValidator)
        <a href="{{ route('campaigns.index') }}" style="font-size:0.875rem; color:var(--color-primary); text-decoration:none; font-weight:500;">
            Voir toutes les campagnes <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i>
        </a>
        @else
        <a href="{{ route('boost.my-requests') }}" style="font-size:0.875rem; color:var(--color-primary); text-decoration:none; font-weight:500;">
            Voir toutes mes campagnes <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i>
        </a>
        @endif
    </div>
    @endif
</div>

@endsection
