@extends('layouts.app')

@section('page-title', 'Mes campagnes')
@section('page-subtitle', 'Suivez l\'état de vos campagnes Media Buyer')

@section('content')

@php
$currentStatus = request('status');
$tabs = [
    ''         => 'Toutes',
    'draft'    => 'Brouillons',
    'pending'  => 'En attente',
    'approved' => 'Approuvées',
    'done'     => 'Boostées',
    'rejected' => 'Rejetées',
    'error'    => 'Erreurs',
];
@endphp

{{-- Header tabs + bouton --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <div class="tab-list">
        @foreach($tabs as $val => $label)
        <a href="{{ route('boost.my-requests', $val ? ['status'=>$val] : []) }}"
           class="tab-item {{ $currentStatus === $val ? 'active' : '' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
    <a href="{{ route('campaigns.create') }}" class="btn-primary">
        <i class="fas fa-plus"></i>
        Nouvelle campagne
    </a>
</div>

{{-- Cards --}}
@forelse($campaigns as $c)
<div class="card" style="margin-bottom:1rem;">
    <div style="display:flex; gap:1rem; align-items:flex-start; padding:1.25rem;">

        {{-- Icône campagne --}}
        <div style="flex-shrink:0;">
            <div style="width:56px; height:56px; background:linear-gradient(135deg,#eef2ff,#f3e8ff); border-radius:0.625rem; display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-layer-group" style="font-size:1.5rem; color:#818cf8;"></i>
            </div>
        </div>

        {{-- Info campagne --}}
        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.75rem; margin-bottom:0.625rem;">
                <div>
                    <div style="font-weight:600; color:#0f172a; margin-bottom:0.125rem;">{{ $c->campaign_name }}</div>
                    <div style="font-size:0.8125rem; color:#64748b; font-family:monospace;">{{ $c->post_id }}</div>
                </div>
                <span class="badge-status {{ $c->status_class }}" style="flex-shrink:0;">{{ $c->status_label }}</span>
            </div>

            <div style="display:flex; gap:1.25rem; flex-wrap:wrap; font-size:0.8125rem; color:#64748b;">
                <span style="font-weight:600; color:var(--color-primary);">
                    <i class="fas fa-coins" style="margin-right:0.25rem; color:#94a3b8;"></i>
                    {{ $c->budget_formatted }}
                </span>
                <span>
                    <i class="fas fa-calendar-days" style="margin-right:0.25rem; color:#94a3b8;"></i>
                    {{ $c->duration_days }} jour(s)
                </span>
                <span>
                    <i class="fas fa-bullseye" style="margin-right:0.25rem; color:#94a3b8;"></i>
                    {{ str_replace('OUTCOME_', '', $c->campaign_objective) }}
                </span>
                <span>
                    <i class="fas fa-clock" style="margin-right:0.25rem; color:#94a3b8;"></i>
                    {{ $c->created_at->diffForHumans() }}
                </span>
            </div>

            @if(in_array($c->execution_status, ['rejected','error']) && $c->error_message)
            <div style="margin-top:0.625rem; padding:0.5rem 0.75rem; background:#fef2f2; border:1px solid #fecaca; border-radius:0.5rem; font-size:0.8125rem; color:#991b1b;">
                <i class="fas fa-times-circle" style="margin-right:0.375rem;"></i>
                <strong>{{ $c->execution_status === 'rejected' ? 'Rejeté' : 'Erreur' }} :</strong>
                {{ Str::limit($c->error_message, 120) }}
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div style="display:flex; flex-direction:column; gap:0.5rem; flex-shrink:0;">
            <a href="{{ route('campaigns.show', $c->id) }}" class="btn-secondary btn-sm">
                <i class="fas fa-eye"></i>
                Détail
            </a>
            @if(in_array($c->execution_status, ['draft','rejected']))
            <form method="POST" action="{{ route('campaigns.submit', $c->id) }}">
                @csrf
                <button type="submit" class="btn-primary btn-sm" style="width:100%;">
                    <i class="fas fa-paper-plane"></i>
                    Soumettre
                </button>
            </form>
            @endif
        </div>

    </div>
</div>
@empty
<div class="card">
    <div class="card-body" style="text-align:center; padding:4rem 1.25rem; color:#94a3b8;">
        <i class="fas fa-layer-group" style="font-size:3rem; color:#e2e8f0; display:block; margin-bottom:1rem;"></i>
        <div style="font-size:1rem; font-weight:500; margin-bottom:0.375rem; color:#64748b;">Aucune campagne trouvée</div>
        <div style="font-size:0.875rem; margin-bottom:1.5rem;">
            @if($currentStatus)
            Aucune campagne avec ce statut.
            @else
            Créez votre première campagne Media Buyer !
            @endif
        </div>
        <a href="{{ route('campaigns.create') }}" class="btn-primary" style="display:inline-flex;">
            <i class="fas fa-plus"></i>
            Nouvelle campagne
        </a>
    </div>
</div>
@endforelse

{{-- Pagination --}}
@if($campaigns->hasPages())
<div style="margin-top:1.5rem; display:flex; justify-content:center;">
    {{ $campaigns->links() }}
</div>
@endif

@endsection
