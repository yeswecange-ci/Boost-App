@extends('layouts.app')

@section('page-title', 'Historique des campagnes')
@section('page-subtitle', 'Toutes les campagnes Media Buyer')

@section('content')

@php
$currentStatus = request('status');
$tabs = [
    ''         => 'Toutes',
    'pending'  => 'En attente',
    'approved' => 'Approuvées',
    'done'     => 'Boostées',
    'error'    => 'Erreurs/Rejetées',
];
@endphp

{{-- Filtres --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <div class="tab-list">
        @foreach($tabs as $val => $label)
        <a href="{{ route('boost.all', $val ? ['status'=>$val] : []) }}"
           class="tab-item {{ $currentStatus === $val ? 'active' : '' }}">
            {{ $label }}
            @if($val === 'pending')
                @php $pendingCount = \App\Models\BoostCampaign::whereIn('execution_status',['pending_n1','pending_n2'])->count(); @endphp
                @if($pendingCount > 0)
                <span style="margin-left:0.25rem; background:#ef4444; color:#fff; font-size:0.6rem; font-weight:700; padding:0.1rem 0.4rem; border-radius:9999px;">
                    {{ $pendingCount }}
                </span>
                @endif
            @endif
        </a>
        @endforeach
    </div>

    <div style="font-size:0.875rem; color:#94a3b8;">
        {{ $campaigns->total() }} résultat(s)
    </div>
</div>

{{-- Table --}}
<div class="card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Campagne</th>
                    <th>Opérateur</th>
                    <th>Budget</th>
                    <th>Durée</th>
                    <th>Pays</th>
                    <th>Statut</th>
                    <th>Mis à jour</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $c)
                <tr>
                    <td style="color:#94a3b8; font-size:0.8125rem; font-weight:500;">#{{ $c->id }}</td>
                    <td>
                        <div style="font-weight:500; font-size:0.875rem; color:#0f172a;">{{ $c->campaign_name }}</div>
                        <div style="font-size:0.75rem; color:#94a3b8; font-family:monospace;">{{ $c->post_id }}</div>
                    </td>
                    <td>
                        <span style="font-size:0.875rem; color:#374151;">{{ $c->user?->name ?? '(supprimé)' }}</span>
                    </td>
                    <td>
                        <div style="font-weight:600; color:var(--color-primary); font-size:0.875rem;">
                            {{ $c->budget_formatted }}
                        </div>
                        <div style="font-size:0.75rem; color:#94a3b8;">{{ $c->budget_type === 'lifetime_budget' ? 'Total' : '/jour' }}</div>
                    </td>
                    <td style="font-size:0.875rem; color:#374151;">
                        {{ $c->duration_days }}j
                    </td>
                    <td>
                        <div style="display:flex; flex-wrap:wrap; gap:0.25rem; max-width:140px;">
                            @foreach(array_slice((array)$c->countries, 0, 3) as $country)
                            <span style="padding:0.125rem 0.375rem; background:var(--color-primary-light); color:var(--color-primary); border-radius:9999px; font-size:0.6875rem; font-weight:500;">
                                {{ $country }}
                            </span>
                            @endforeach
                            @if(count((array)$c->countries) > 3)
                            <span style="font-size:0.6875rem; color:#94a3b8;">+{{ count((array)$c->countries) - 3 }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge-status {{ $c->status_class }}">{{ $c->status_label }}</span>
                        @if(in_array($c->execution_status, ['rejected','error']) && $c->error_message)
                        <div style="font-size:0.6875rem; color:#ef4444; margin-top:0.25rem; max-width:140px;">
                            {{ Str::limit($c->error_message, 40) }}
                        </div>
                        @endif
                    </td>
                    <td style="font-size:0.8125rem; color:#94a3b8; white-space:nowrap;">
                        {{ $c->updated_at->format('d/m/Y') }}<br>
                        <span style="font-size:0.75rem;">{{ $c->updated_at->format('H:i') }}</span>
                    </td>
                    <td>
                        <div style="display:flex; gap:0.375rem;">
                            <a href="{{ route('campaigns.show', $c->id) }}" class="btn-secondary btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if(in_array($c->execution_status, ['pending_n1','pending_n2']) && auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']))
                            <a href="{{ route('campaigns.pending') }}" class="btn-primary btn-sm">
                                <i class="fas fa-gavel"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:3rem 1rem; color:#94a3b8;">
                        <i class="fas fa-search" style="font-size:2rem; color:#e2e8f0; display:block; margin-bottom:0.75rem;"></i>
                        Aucune campagne trouvée pour ce filtre.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($campaigns->hasPages())
    <div class="card-footer" style="display:flex; justify-content:center;">
        {{ $campaigns->links() }}
    </div>
    @endif
</div>

@endsection
