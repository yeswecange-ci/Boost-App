@extends('layouts.app')

@section('page-title', 'Mes demandes de boost')
@section('page-subtitle', 'Suivez l\'état de vos demandes')

@section('content')

@php
$statusMap = [
    'draft'        => ['label'=>'Brouillon',       'class'=>'badge-status-draft'],
    'pending_n1'   => ['label'=>'En attente N+1',  'class'=>'badge-status-pending'],
    'pending_n2'   => ['label'=>'En attente N+2',  'class'=>'badge-status-pending'],
    'rejected_n1'  => ['label'=>'Rejeté N+1',      'class'=>'badge-status-rejected'],
    'rejected_n2'  => ['label'=>'Rejeté N+2',      'class'=>'badge-status-rejected'],
    'approved'     => ['label'=>'Approuvé',        'class'=>'badge-status-approved'],
    'creating'     => ['label'=>'En création…',   'class'=>'badge-status-pending'],
    'paused_ready' => ['label'=>'Prêt à activer',  'class'=>'badge-status-created'],
    'active'       => ['label'=>'Actif',           'class'=>'badge-status-active'],
    'paused'       => ['label'=>'En pause',        'class'=>'badge-status-paused'],
    'completed'    => ['label'=>'Terminé',         'class'=>'badge-status-completed'],
];
$currentStatus = request('status');
$tabs = [
    ''         => 'Tous',
    'draft'    => 'Brouillons',
    'pending'  => 'En attente',
    'approved' => 'Approuvés',
    'active'   => 'Actifs',
    'rejected' => 'Rejetés',
    'completed'=> 'Terminés',
];
@endphp

{{-- Header --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <div class="tab-list">
        @foreach($tabs as $val => $label)
        <a href="{{ route('boost.my-requests', $val ? ['status'=>$val] : []) }}"
           class="tab-item {{ $currentStatus === $val ? 'active' : '' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
    <a href="{{ route('posts.index') }}" class="btn-primary">
        <i class="fas fa-plus"></i>
        Nouveau boost
    </a>
</div>

{{-- Cards --}}
@forelse($boosts as $boost)
@php $s = $statusMap[$boost->status] ?? ['label'=>ucfirst($boost->status),'class'=>'badge-status-draft']; @endphp
<div class="card" style="margin-bottom:1rem; overflow:hidden;">
    <div style="display:flex; gap:1rem; align-items:flex-start; padding:1.25rem;">

        {{-- Thumbnail --}}
        <div style="flex-shrink:0;">
            @if($boost->post_thumbnail)
            <img src="{{ $boost->post_thumbnail }}"
                 style="width:80px; height:80px; object-fit:cover; border-radius:0.625rem;">
            @else
            <div style="width:80px; height:80px; background:linear-gradient(135deg,#eef2ff,#f3e8ff); border-radius:0.625rem; display:flex; align-items:center; justify-content:center;">
                <i class="fab fa-facebook" style="font-size:1.75rem; color:#a5b4fc;"></i>
            </div>
            @endif
        </div>

        {{-- Info --}}
        <div style="flex:1; min-width:0;">
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.75rem; margin-bottom:0.5rem;">
                <div>
                    <div style="font-weight:600; color:#0f172a; margin-bottom:0.125rem;">{{ $boost->page_name }}</div>
                    <div style="font-size:0.8125rem; color:#64748b; line-height:1.4;">
                        {{ Str::limit($boost->post_message, 80) }}
                    </div>
                </div>
                <span class="badge-status {{ $s['class'] }}" style="flex-shrink:0;">{{ $s['label'] }}</span>
            </div>

            <div style="display:flex; gap:1.25rem; flex-wrap:wrap; font-size:0.8125rem; color:#64748b;">
                <span>
                    <i class="fas fa-calendar" style="margin-right:0.25rem; color:#94a3b8;"></i>
                    {{ $boost->start_date->format('d/m/Y') }} → {{ $boost->end_date->format('d/m/Y') }}
                </span>
                <span style="font-weight:600; color:var(--color-primary);">
                    <i class="fas fa-coins" style="margin-right:0.25rem; color:#94a3b8;"></i>
                    {{ number_format($boost->budget, 0, ',', ' ') }} {{ $boost->currency }}
                </span>
                <span>
                    <i class="fas fa-clock" style="margin-right:0.25rem; color:#94a3b8;"></i>
                    {{ $boost->created_at->diffForHumans() }}
                </span>
            </div>

            @if(in_array($boost->status, ['rejected_n1','rejected_n2']) && $boost->rejection_reason)
            <div style="margin-top:0.625rem; padding:0.5rem 0.75rem; background:#fef2f2; border:1px solid #fecaca; border-radius:0.5rem; font-size:0.8125rem; color:#991b1b;">
                <i class="fas fa-times-circle" style="margin-right:0.375rem;"></i>
                <strong>Rejeté {{ $boost->status === 'rejected_n1' ? 'N+1' : 'N+2' }} :</strong>
                {{ Str::limit($boost->rejection_reason, 100) }}
            </div>
            @endif
        </div>

        {{-- Actions --}}
        <div style="display:flex; flex-direction:column; gap:0.5rem; flex-shrink:0;">
            <a href="{{ route('boost.show', $boost->id) }}" class="btn-secondary btn-sm">
                <i class="fas fa-eye"></i>
                Détail
            </a>
            @if(in_array($boost->status, ['draft','rejected_n1','rejected_n2']))
            <form method="POST" action="{{ route('boost.submit', $boost->id) }}">
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
        <i class="fas fa-rocket" style="font-size:3rem; color:#e2e8f0; display:block; margin-bottom:1rem;"></i>
        <div style="font-size:1rem; font-weight:500; margin-bottom:0.375rem; color:#64748b;">Aucune demande trouvée</div>
        <div style="font-size:0.875rem; margin-bottom:1.5rem;">
            @if($currentStatus)
            Aucun boost avec ce statut.
            @else
            Commencez par booster un post Facebook !
            @endif
        </div>
        <a href="{{ route('posts.index') }}" class="btn-primary" style="display:inline-flex;">
            <i class="fas fa-rocket"></i>
            Voir les posts
        </a>
    </div>
</div>
@endforelse

{{-- Pagination --}}
@if($boosts->hasPages())
<div style="margin-top:1.5rem; display:flex; justify-content:center;">
    {{ $boosts->links() }}
</div>
@endif

@endsection
