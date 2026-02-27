@extends('layouts.app')

@section('page-title', 'Historique complet')
@section('page-subtitle', 'Toutes les demandes de boost')

@section('content')

@php
$statusMap = [
    'draft'        => ['label'=>'Brouillon',      'class'=>'badge-status-draft'],
    'pending_n1'   => ['label'=>'Attente N+1',    'class'=>'badge-status-pending'],
    'rejected_n1'  => ['label'=>'Rejeté N+1',     'class'=>'badge-status-rejected'],
    'pending_n2'   => ['label'=>'Attente N+2',    'class'=>'badge-status-pending'],
    'rejected_n2'  => ['label'=>'Rejeté N+2',     'class'=>'badge-status-rejected'],
    'approved'     => ['label'=>'Approuvé',       'class'=>'badge-status-approved'],
    'creating'     => ['label'=>'En création…',  'class'=>'badge-status-pending'],
    'paused_ready' => ['label'=>'Prêt activer',   'class'=>'badge-status-created'],
    'active'       => ['label'=>'Actif',          'class'=>'badge-status-active'],
    'paused'       => ['label'=>'En pause',       'class'=>'badge-status-paused'],
    'completed'    => ['label'=>'Terminé',        'class'=>'badge-status-completed'],
    'cancelled'    => ['label'=>'Annulé',          'class'=>'badge-status-rejected'],
    'failed'       => ['label'=>'Échec',          'class'=>'badge-status-rejected'],
];
$currentStatus = request('status');
$tabs = [
    ''         => 'Tous',
    'pending'  => 'En attente',
    'approved' => 'Approuvés',
    'active'   => 'Actifs',
    'rejected' => 'Rejetés',
    'completed'=> 'Terminés',
];
@endphp

{{-- Filters --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <div class="tab-list">
        @foreach($tabs as $val => $label)
        <a href="{{ route('boost.all', $val ? ['status'=>$val] : []) }}"
           class="tab-item {{ $currentStatus === $val ? 'active' : '' }}">
            {{ $label }}
            @if($val === 'pending')
                @php $pendingCount = \App\Models\BoostRequest::whereIn('status',['pending_n1','pending_n2'])->count(); @endphp
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
        {{ $boosts->total() }} résultat(s)
    </div>
</div>

{{-- Table --}}
<div class="card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Post / Page</th>
                    <th>Opérateur</th>
                    <th>Période</th>
                    <th>Budget</th>
                    <th>Audience</th>
                    <th>Statut</th>
                    <th>Soumis le</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($boosts as $boost)
                @php $s = $statusMap[$boost->status] ?? ['label'=>ucfirst($boost->status),'class'=>'badge-status-draft']; @endphp
                <tr>
                    <td style="color:#94a3b8; font-size:0.8125rem; font-weight:500;">#{{ $boost->id }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:0.625rem;">
                            @if($boost->post_thumbnail)
                            <img src="{{ $boost->post_thumbnail }}"
                                 style="width:44px; height:44px; object-fit:cover; border-radius:0.5rem; flex-shrink:0;">
                            @else
                            <div style="width:44px; height:44px; background:linear-gradient(135deg,#eef2ff,#f3e8ff); border-radius:0.5rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="fab fa-facebook" style="color:#a5b4fc;"></i>
                            </div>
                            @endif
                            <div>
                                <div style="font-weight:500; font-size:0.875rem; color:#0f172a;">{{ $boost->page_name }}</div>
                                <div style="font-size:0.75rem; color:#94a3b8;">{{ Str::limit($boost->post_message, 40) }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; gap:0.5rem;">
                            <x-user-avatar :user="$boost->operator" :size="28" />
                            <span style="font-size:0.875rem; color:#374151;">{{ $boost->operator->name }}</span>
                        </div>
                    </td>
                    <td>
                        <div style="font-size:0.8125rem; color:#374151;">{{ $boost->start_date->format('d/m/Y') }}</div>
                        <div style="font-size:0.75rem; color:#94a3b8;">→ {{ $boost->end_date->format('d/m/Y') }}</div>
                    </td>
                    <td>
                        <div style="font-weight:600; color:var(--color-primary); font-size:0.875rem;">
                            {{ number_format($boost->budget, 0, ',', ' ') }}
                        </div>
                        <div style="font-size:0.75rem; color:#94a3b8;">{{ $boost->currency }}</div>
                    </td>
                    <td>
                        <div style="display:flex; flex-wrap:wrap; gap:0.25rem; max-width:160px;">
                            @foreach($boost->target['countries'] as $country)
                            <span style="padding:0.125rem 0.375rem; background:var(--color-primary-light); color:var(--color-primary); border-radius:9999px; font-size:0.6875rem; font-weight:500;">
                                {{ $country }}
                            </span>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        <span class="badge-status {{ $s['class'] }}">{{ $s['label'] }}</span>
                        @if(in_array($boost->status,['rejected_n1','rejected_n2']) && $boost->rejection_reason)
                        <div style="font-size:0.6875rem; color:#ef4444; margin-top:0.25rem; max-width:140px;">
                            {{ Str::limit($boost->rejection_reason, 40) }}
                        </div>
                        @endif
                    </td>
                    <td style="font-size:0.8125rem; color:#94a3b8; white-space:nowrap;">
                        {{ $boost->updated_at->format('d/m/Y') }}<br>
                        <span style="font-size:0.75rem;">{{ $boost->updated_at->format('H:i') }}</span>
                    </td>
                    <td>
                        <div style="display:flex; gap:0.375rem;">
                            <a href="{{ route('boost.show', $boost->id) }}" class="btn-secondary btn-sm">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($boost->status === 'pending_n1' && auth()->user()->hasRole(['validator_n1','validator','admin']))
                            <a href="{{ route('boost.pending-n1') }}" class="btn-primary btn-sm">
                                <i class="fas fa-gavel"></i>
                            </a>
                            @elseif($boost->status === 'pending_n2' && auth()->user()->hasRole(['validator_n2','admin']))
                            <a href="{{ route('boost.pending-n2') }}" class="btn-primary btn-sm">
                                <i class="fas fa-shield-halved"></i>
                            </a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:3rem 1rem; color:#94a3b8;">
                        <i class="fas fa-search" style="font-size:2rem; color:#e2e8f0; display:block; margin-bottom:0.75rem;"></i>
                        Aucun boost trouvé pour ce filtre.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($boosts->hasPages())
    <div class="card-footer" style="display:flex; justify-content:center;">
        {{ $boosts->links() }}
    </div>
    @endif
</div>

@endsection
