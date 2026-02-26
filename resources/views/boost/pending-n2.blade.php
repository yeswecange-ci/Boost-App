@extends('layouts.app')

@section('page-title', 'Validation N+2')
@section('page-subtitle', 'Demandes escaladées — sensibilité moyenne ou élevée')

@section('content')

{{-- Header stats --}}
<div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem;">
    <div style="display:flex; align-items:center; gap:0.75rem;">
        <div class="stat-icon" style="background:#f3e8ff; color:#6d28d9;">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div>
            <div style="font-size:1.5rem; font-weight:700; color:#0f172a;">{{ $boosts->total() }}</div>
            <div style="font-size:0.8125rem; color:#64748b;">demande(s) en attente N+2</div>
        </div>
    </div>
    <a href="{{ route('boost.all') }}" class="btn-secondary">
        <i class="fas fa-list"></i>
        Historique complet
    </a>
</div>

{{-- Cards --}}
@forelse($boosts as $boost)
<div class="card" style="margin-bottom:1.25rem;" x-data="{ rejectOpen: false, historyOpen: false }">

    <div class="card-body">
        <div style="display:flex; gap:1rem; align-items:flex-start;">

            {{-- Thumbnail --}}
            <div style="flex-shrink:0;">
                @if($boost->post_thumbnail)
                <img src="{{ $boost->post_thumbnail }}"
                     style="width:88px; height:88px; object-fit:cover; border-radius:0.625rem;">
                @else
                <div style="width:88px; height:88px; background:linear-gradient(135deg,#f3e8ff,#ede9fe); border-radius:0.625rem; display:flex; align-items:center; justify-content:center;">
                    <i class="fab fa-facebook" style="font-size:2rem; color:#c4b5fd;"></i>
                </div>
                @endif
            </div>

            {{-- Infos --}}
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:0.75rem; margin-bottom:0.5rem;">
                    <div>
                        <div style="font-weight:700; font-size:1rem; color:#0f172a; margin-bottom:0.125rem;">
                            Boost #{{ $boost->id }} — {{ $boost->page_name }}
                        </div>
                        <div style="font-size:0.8125rem; color:#64748b; line-height:1.5;">
                            {{ Str::limit($boost->post_message, 100) }}
                        </div>
                    </div>
                    <div style="display:flex; flex-direction:column; align-items:flex-end; gap:0.375rem; flex-shrink:0;">
                        <span class="badge-status" style="background:#f3e8ff; color:#6d28d9;">
                            <i class="fas fa-shield-halved"></i>
                            En attente N+2
                        </span>
                        @php
                        $sensColors = [
                            'moyenne' => ['bg'=>'#fef9c3','color'=>'#854d0e','label'=>'Sensibilité moyenne'],
                            'elevee'  => ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'Sensibilité élevée'],
                        ];
                        $sc = $sensColors[$boost->sensitivity] ?? ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'Sensibilité élevée'];
                        @endphp
                        <span style="padding:0.2rem 0.625rem; background:{{ $sc['bg'] }}; color:{{ $sc['color'] }}; border-radius:9999px; font-size:0.75rem; font-weight:600;">
                            <i class="fas fa-exclamation-triangle" style="font-size:0.6875rem;"></i>
                            {{ $sc['label'] }}
                        </span>
                    </div>
                </div>

                {{-- Meta info --}}
                <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-bottom:0.875rem;">
                    <span style="display:inline-flex; align-items:center; gap:0.375rem; padding:0.25rem 0.625rem; background:#f8fafc; border:1px solid var(--color-border); border-radius:9999px; font-size:0.8125rem; color:#374151;">
                        <i class="fas fa-user" style="color:#94a3b8; font-size:0.6875rem;"></i>
                        {{ $boost->operator->name }}
                    </span>
                    <span style="display:inline-flex; align-items:center; gap:0.375rem; padding:0.25rem 0.625rem; background:#f8fafc; border:1px solid var(--color-border); border-radius:9999px; font-size:0.8125rem; color:#374151;">
                        <i class="fas fa-calendar" style="color:#94a3b8; font-size:0.6875rem;"></i>
                        {{ $boost->start_date->format('d/m/Y') }} → {{ $boost->end_date->format('d/m/Y') }}
                    </span>
                    <span style="display:inline-flex; align-items:center; gap:0.375rem; padding:0.25rem 0.625rem; background:var(--color-primary-light); border-radius:9999px; font-size:0.8125rem; color:var(--color-primary); font-weight:600;">
                        <i class="fas fa-coins" style="font-size:0.6875rem;"></i>
                        {{ number_format($boost->budget, 0, ',', ' ') }} {{ $boost->currency }}
                    </span>
                </div>

                {{-- Audience --}}
                <div style="display:flex; flex-wrap:wrap; gap:0.375rem; margin-bottom:0.875rem;">
                    <span style="padding:0.2rem 0.5rem; background:#f1f5f9; border-radius:9999px; font-size:0.75rem; color:#64748b;">
                        {{ $boost->target['age_min'] }}–{{ $boost->target['age_max'] }} ans
                    </span>
                    <span style="padding:0.2rem 0.5rem; background:#f1f5f9; border-radius:9999px; font-size:0.75rem; color:#64748b;">
                        {{ ['all'=>'Tous','male'=>'Hommes','female'=>'Femmes'][$boost->target['gender']] }}
                    </span>
                    @foreach($boost->target['countries'] as $country)
                    <span style="padding:0.2rem 0.5rem; background:var(--color-primary-light); color:var(--color-primary); border-radius:9999px; font-size:0.75rem; font-weight:500;">
                        {{ $country }}
                    </span>
                    @endforeach
                </div>

                {{-- Validation N+1 history --}}
                @if($boost->approvals->count() > 0)
                <div style="margin-bottom:0.875rem;">
                    <button @click="historyOpen = !historyOpen"
                            style="display:flex; align-items:center; gap:0.5rem; font-size:0.8125rem; color:var(--color-primary); background:none; border:none; cursor:pointer; padding:0;">
                        <i class="fas fa-history" style="font-size:0.6875rem;"></i>
                        <span x-text="historyOpen ? 'Masquer historique N+1' : 'Voir validation N+1'">Voir validation N+1</span>
                    </button>
                    <div x-show="historyOpen" x-cloak style="margin-top:0.625rem; padding:0.75rem; background:#f8fafc; border-radius:0.5rem; border:1px solid var(--color-border);">
                        @foreach($boost->approvals as $approval)
                        <div style="font-size:0.8125rem; color:#374151;">
                            <span style="font-weight:600;">{{ $approval->user->name }}</span>
                            <span style="color:#94a3b8;"> (N+{{ $approval->level === 'N1' ? '1' : '2' }}) —</span>
                            <span style="color:{{ $approval->isApproved() ? '#16a34a' : '#dc2626' }}; font-weight:600;">
                                {{ $approval->isApproved() ? 'Approuvé' : 'Rejeté' }}
                            </span>
                            <span style="color:#94a3b8;"> · {{ $approval->created_at->format('d/m H:i') }}</span>
                            @if($approval->comment)
                            <div style="margin-top:0.25rem; color:#64748b; font-style:italic;">"{{ $approval->comment }}"</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Action buttons --}}
                <div style="display:flex; gap:0.625rem; flex-wrap:wrap; align-items:center;">

                    <form method="POST" action="{{ route('boost.approve-n2', $boost->id) }}"
                          onsubmit="return confirm('Approuver définitivement le boost #{{ $boost->id }} ?')">
                        @csrf
                        <button type="submit" class="btn-success btn-sm">
                            <i class="fas fa-check-double"></i>
                            Approuver (Final)
                        </button>
                    </form>

                    <button @click="rejectOpen = !rejectOpen" class="btn-danger btn-sm">
                        <i class="fas fa-times"></i>
                        <span x-text="rejectOpen ? 'Annuler' : 'Rejeter'">Rejeter</span>
                    </button>

                    <a href="{{ route('boost.show', $boost->id) }}" class="btn-secondary btn-sm">
                        <i class="fas fa-eye"></i>
                        Détail
                    </a>

                </div>

                {{-- Reject form --}}
                <div x-show="rejectOpen" x-cloak style="margin-top:0.875rem;">
                    <form method="POST" action="{{ route('boost.reject-n2', $boost->id) }}">
                        @csrf
                        <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                            <textarea name="rejection_reason"
                                      class="form-control"
                                      rows="2"
                                      placeholder="Raison du rejet N+2 (minimum 10 caractères)..."
                                      required
                                      style="flex:1; resize:vertical;"></textarea>
                            <button type="submit" class="btn-danger" style="align-self:flex-end; flex-shrink:0;">
                                <i class="fas fa-times-circle"></i>
                                Confirmer
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <div class="card-footer" style="font-size:0.8125rem; color:#94a3b8;">
        <i class="fas fa-check" style="margin-right:0.375rem; color:#22c55e;"></i>
        Validé N+1 · Soumis par <strong style="color:#64748b;">{{ $boost->operator->name }}</strong>
        · {{ $boost->updated_at->diffForHumans() }}
    </div>

</div>
@empty
<div class="card">
    <div class="card-body" style="text-align:center; padding:4rem 1.25rem;">
        <i class="fas fa-shield-halved" style="font-size:3rem; color:#a78bfa; display:block; margin-bottom:1rem;"></i>
        <div style="font-size:1rem; font-weight:600; color:#0f172a; margin-bottom:0.375rem;">
            File N+2 vide !
        </div>
        <div style="font-size:0.875rem; color:#64748b;">
            Aucune demande en attente de validation N+2.
        </div>
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
