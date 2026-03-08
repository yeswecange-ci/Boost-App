@extends('layouts.app')

@section('page-title', 'Campagnes à valider')
@section('page-subtitle', 'Examinez et approuvez les campagnes soumises par les opérateurs')

@php
    $user    = auth()->user();
    $isAdmin = $user->hasRole('admin');
    $isN2    = $user->hasRole(['validator_n2','admin']);
    $isN1    = $user->hasRole(['validator_n1','validator','admin']);
@endphp

@section('content')

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

{{-- Compteurs N+1 / N+2 --}}
<div style="display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
    @if($isN1)
    <div class="stat-card" style="max-width:220px;">
        <div class="stat-icon" style="background:#fef9c3; color:#854d0e;">
            <i class="fas fa-clock"></i>
        </div>
        <div>
            <div class="stat-value">{{ $pendingN1Count }}</div>
            <div class="stat-label">En attente N+1</div>
        </div>
    </div>
    @endif
    @if($isN2)
    <div class="stat-card" style="max-width:220px;">
        <div class="stat-icon" style="background:#dbeafe; color:#1d4ed8;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <div class="stat-value">{{ $pendingN2Count }}</div>
            <div class="stat-label">En attente N+2</div>
        </div>
    </div>
    @endif
</div>

@if($campaigns->isEmpty())
<div class="card">
    <div class="card-body" style="text-align:center; padding:3rem 1.25rem; color:var(--color-muted);">
        <i class="fas fa-check-circle" style="font-size:2.5rem; color:#86efac; display:block; margin-bottom:1rem;"></i>
        <div style="font-weight:600; margin-bottom:.375rem;">Aucune campagne en attente</div>
        <div style="font-size:.875rem;">Toutes les soumissions ont été traitées.</div>
    </div>
</div>

@else

<div style="display:flex; flex-direction:column; gap:1rem;">
    @foreach($campaigns as $c)
    <div class="card" x-data="{ open: false, rejectOpen: false, reason: '' }">

        {{-- Header cliquable --}}
        <div class="card-header" style="cursor:pointer; justify-content:space-between;" @click="open = !open">
            <div style="display:flex; align-items:center; gap:.75rem;">
                <i class="fas fa-layer-group" style="color:var(--color-primary);"></i>
                <div>
                    <div style="font-weight:600; font-size:.9375rem;">{{ $c->campaign_name }}</div>
                    <div style="font-size:.75rem; color:var(--color-muted); margin-top:.125rem;">
                        #{{ $c->id }} ·
                        par <strong>{{ $c->user?->name ?? '—' }}</strong> ·
                        {{ $c->created_at->diffForHumans() }}
                    </div>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:.75rem;">
                <span style="font-weight:700; color:var(--color-primary);">{{ $c->budget_formatted }}</span>
                <span style="font-size:.8125rem; color:var(--color-muted);">{{ $c->duration_days }}j</span>
                @if($c->execution_status === 'pending_n1')
                <span class="badge-status badge-status-pending" style="font-size:.6875rem;">N+1</span>
                @elseif($c->execution_status === 'pending_n2')
                <span class="badge-status" style="font-size:.6875rem; background:#dbeafe; color:#1d4ed8; border:1px solid #93c5fd;">N+2</span>
                @endif
                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" style="color:var(--color-muted); font-size:.8125rem;"></i>
            </div>
        </div>

        {{-- Détails (collapsible) --}}
        <div x-show="open" x-transition>
            <div class="card-body" style="border-top:1px solid var(--color-border);">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px,1fr)); gap:1rem;">

                    @foreach([
                        'Post ID'      => $c->post_id,
                        'Objectif'     => str_replace('OUTCOME_','',$c->campaign_objective),
                        'Pays'         => implode(', ', $c->countries ?? []),
                        'Démarrage'    => $c->campaign_status,
                        'Optimisation' => $c->optimization_goal,
                        'Facturation'  => $c->billing_event,
                    ] as $lbl => $val)
                    <div>
                        <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-bottom:.25rem;">{{ $lbl }}</div>
                        <div style="font-size:.875rem; font-weight:500; font-family:{{ $lbl === 'Post ID' ? 'monospace' : 'inherit' }};">{{ $val }}</div>
                    </div>
                    @endforeach

                    @if($c->interests)
                    <div>
                        <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-bottom:.25rem;">Intérêts</div>
                        <div style="font-size:.875rem; font-weight:500;">{{ count($c->interests) }} centre(s)</div>
                    </div>
                    @endif

                    @if($c->existing_campaign_id)
                    <div>
                        <div style="font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin-bottom:.25rem;">Campagne existante</div>
                        <div style="font-size:.8125rem; font-family:monospace; color:var(--color-primary);">{{ $c->existing_campaign_id }}</div>
                    </div>
                    @endif

                </div>
            </div>

            {{-- Actions --}}
            <div style="padding:.875rem 1.25rem; border-top:1px solid var(--color-border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.75rem;">
                <a href="{{ route('campaigns.show', $c->id) }}" class="btn-secondary btn-sm">
                    <i class="fas fa-eye"></i> Voir le détail
                </a>
                <div style="display:flex; gap:.625rem;">
                    {{-- Rejeter --}}
                    <button type="button" class="btn-danger btn-sm" @click="rejectOpen = true">
                        <i class="fas fa-times"></i> Rejeter
                    </button>
                    {{-- Approuver --}}
                    <form method="POST" action="{{ route('campaigns.approve', $c->id) }}">
                        @csrf
                        <button type="submit" class="btn-success btn-sm">
                            @if($c->execution_status === 'pending_n1')
                            <i class="fas fa-check"></i> Valider N+1 →N+2
                            @else
                            <i class="fas fa-check-double"></i> Valider N+2 — Approuver
                            @endif
                        </button>
                    </form>
                </div>
            </div>

            {{-- Modal rejet --}}
            <div x-show="rejectOpen" x-transition
                 style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); z-index:50; align-items:center; justify-content:center; padding:1rem;"
                 :style="rejectOpen ? 'display:flex' : 'display:none'">
                <div style="background:#fff; border-radius:.875rem; padding:1.5rem; max-width:480px; width:100%; box-shadow:0 20px 60px rgba(0,0,0,.2);" @click.stop>
                    <h3 style="font-size:1rem; font-weight:700; margin:0 0 .25rem;">
                        Rejeter « {{ Str::limit($c->campaign_name, 40) }} »
                    </h3>
                    <p style="font-size:.875rem; color:var(--color-muted); margin:0 0 1rem;">
                        L'opérateur recevra ce motif et pourra corriger sa campagne.
                    </p>
                    <form method="POST" action="{{ route('campaigns.reject', $c->id) }}">
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

        </div>

    </div>
    @endforeach
</div>

<div style="margin-top:1.25rem;">
    {{ $campaigns->links() }}
</div>

@endif

@endsection
