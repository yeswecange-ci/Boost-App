@extends('layouts.app')

@section('page-title', 'Campagnes Media Buyer')
@section('page-subtitle', 'Agent YWC — Historique des campagnes Meta Ads')

@section('content')

{{-- KPI --}}
<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:1.5rem;">
    @foreach([
        ['label'=>'Total',     'val'=>$counts['all'],     'icon'=>'fa-layer-group', 'color'=>'#4f46e5', 'bg'=>'#eef2ff', 'status'=>''],
        ['label'=>'En attente','val'=>$counts['pending'],  'icon'=>'fa-clock',       'color'=>'#854d0e', 'bg'=>'#fef9c3', 'status'=>'pending'],
        ['label'=>'En cours',  'val'=>$counts['running'],  'icon'=>'fa-spinner',     'color'=>'#1e40af', 'bg'=>'#dbeafe', 'status'=>'running'],
        ['label'=>'Créées',    'val'=>$counts['done'],     'icon'=>'fa-check-circle','color'=>'#15803d', 'bg'=>'#dcfce7', 'status'=>'done'],
        ['label'=>'Erreurs',   'val'=>$counts['error'],    'icon'=>'fa-times-circle','color'=>'#b91c1c', 'bg'=>'#fee2e2', 'status'=>'error'],
    ] as $kpi)
    <div class="stat-card">
        <div class="stat-icon" style="background:{{ $kpi['bg'] }}; color:{{ $kpi['color'] }};">
            <i class="fas {{ $kpi['icon'] }}"></i>
        </div>
        <div>
            <div class="stat-value">{{ $kpi['val'] }}</div>
            <div class="stat-label">{{ $kpi['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Table --}}
<div class="card">
    <div class="card-header" style="justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-layer-group" style="color:var(--color-primary);"></i>
            Campagnes Meta Ads
        </div>
        @can('create', App\Models\BoostCampaign::class)
        <a href="{{ route('campaigns.create') }}" class="btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nouvelle campagne
        </a>
        @else
        @if(auth()->user()->hasRole(['operator','admin']))
        <a href="{{ route('campaigns.create') }}" class="btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nouvelle campagne
        </a>
        @endif
        @endcan
    </div>

    @if($campaigns->isEmpty())
    <div class="card-body" style="text-align:center; padding:3rem 1.25rem; color:var(--color-muted);">
        <i class="fas fa-layer-group" style="font-size:2.5rem; color:#e2e8f0; display:block; margin-bottom:1rem;"></i>
        <div style="font-weight:500; margin-bottom:0.375rem;">Aucune campagne pour l'instant</div>
        <div style="font-size:0.875rem;">Créez votre première campagne Media Buyer.</div>
        @if(auth()->user()->hasRole(['operator','admin']))
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
                    <th>Post ID</th>
                    <th>Budget</th>
                    <th>Durée</th>
                    <th>Objectif</th>
                    @if($isValidator)<th>Opérateur</th>@endif
                    <th>Statut</th>
                    <th>IDs Meta</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $c)
                <tr>
                    <td style="color:var(--color-muted); font-size:0.8125rem;">#{{ $c->id }}</td>
                    <td>
                        <div style="font-weight:500; font-size:0.875rem;">{{ $c->campaign_name }}</div>
                        <div style="font-size:0.75rem; color:var(--color-muted);">{{ $c->adset_name }}</div>
                    </td>
                    <td style="font-family:monospace; font-size:0.8125rem;">{{ $c->post_id }}</td>
                    <td style="font-weight:600; color:var(--color-primary); font-size:0.875rem;">
                        {{ $c->budget_formatted }}
                    </td>
                    <td style="font-size:0.875rem;">{{ $c->duration_days }}j</td>
                    <td style="font-size:0.8125rem; color:var(--color-muted);">
                        {{ str_replace('OUTCOME_', '', $c->campaign_objective) }}
                    </td>
                    @if($isValidator)
                    <td style="font-size:0.875rem;">{{ $c->user?->name ?? '(supprimé)' }}</td>
                    @endif
                    <td>
                        <span class="badge-status {{ $c->status_class }}">{{ $c->status_label }}</span>
                    </td>
                    <td style="font-size:0.75rem; font-family:monospace; color:var(--color-muted);">
                        @if($c->meta_campaign_id)
                        <div title="Campaign ID">C: {{ Str::limit($c->meta_campaign_id, 12) }}</div>
                        @endif
                        @if($c->meta_adset_id)
                        <div title="AdSet ID">AS: {{ Str::limit($c->meta_adset_id, 12) }}</div>
                        @endif
                        @if($c->error_message)
                        <div style="color:#ef4444;" title="{{ $c->error_message }}">
                            <i class="fas fa-exclamation-circle"></i> Erreur
                        </div>
                        @endif
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
    <div class="card-footer">
        {{ $campaigns->links() }}
    </div>
    @endif
</div>

@endsection
