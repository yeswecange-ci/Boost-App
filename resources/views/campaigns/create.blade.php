@extends('layouts.app')

@section('page-title', 'Booster un post')
@section('page-subtitle', 'Choisissez le budget et la durée — n8n gère le reste')

@section('content')

<div style="max-width:600px; margin:0 auto;">

    @if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:1.25rem;">
        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
    </div>
    @endif

    <form method="POST" action="{{ route('campaigns.store') }}" id="boostForm">
        @csrf

        {{-- ── Post à booster ── --}}
        @if($post)

        {{-- Post pré-sélectionné (venu de /posts) --}}
        <input type="hidden" name="post_id" value="{{ $post->post_id }}">

        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <i class="fab fa-facebook" style="color:#1877f2;"></i>
                Post sélectionné
            </div>
            <div class="card-body" style="display:flex; gap:1rem; align-items:flex-start;">
                @if($post->thumbnail_url)
                <img src="{{ $post->thumbnail_url }}" alt=""
                     style="width:80px; height:80px; object-fit:cover; border-radius:0.5rem; flex-shrink:0;">
                @else
                <div style="width:80px; height:80px; border-radius:0.5rem; background:linear-gradient(135deg,#eef2ff,#f3e8ff); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fab fa-facebook" style="font-size:2rem; color:#a5b4fc;"></i>
                </div>
                @endif
                <div style="flex:1; min-width:0;">
                    <div style="font-size:0.875rem; color:#374151; line-height:1.5; margin-bottom:0.5rem;
                         display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;">
                        {{ $post->message ?: '(Aucun texte)' }}
                    </div>
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                        <span style="font-size:0.75rem; color:#94a3b8;">
                            <i class="far fa-clock" style="margin-right:0.25rem;"></i>
                            {{ $post->posted_at?->diffForHumans() ?? '—' }}
                        </span>
                        <span style="font-size:0.75rem; font-family:monospace; color:#6366f1;" title="Post ID">
                            {{ $post->post_id }}
                        </span>
                        @if($post->page)
                        <span style="font-size:0.75rem; color:#94a3b8;">
                            <i class="fab fa-facebook" style="color:#1877f2; margin-right:0.25rem;"></i>
                            {{ $post->page->page_name }}
                        </span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('posts.index') }}" class="btn-secondary btn-sm" style="flex-shrink:0;" title="Changer de post">
                    <i class="fas fa-exchange-alt"></i>
                </a>
            </div>
        </div>

        @else

        {{-- Sélection manuelle du post --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <i class="fab fa-facebook" style="color:#1877f2;"></i>
                Choisir un post à booster
            </div>
            <div class="card-body">
                @if($posts && $posts->isNotEmpty())
                <div style="display:flex; flex-direction:column; gap:0.625rem; max-height:360px; overflow-y:auto;">
                    @foreach($posts as $p)
                    <label style="display:flex; gap:0.75rem; align-items:center; padding:0.75rem; border:2px solid var(--color-border); border-radius:0.625rem; cursor:pointer; transition:border-color .15s;">
                        <input type="radio" name="post_id" value="{{ $p->post_id }}"
                               style="accent-color:var(--color-primary); flex-shrink:0;"
                               {{ old('post_id') === $p->post_id ? 'checked' : '' }}>
                        @if($p->thumbnail_url)
                        <img src="{{ $p->thumbnail_url }}" alt=""
                             style="width:52px; height:52px; object-fit:cover; border-radius:0.375rem; flex-shrink:0;">
                        @else
                        <div style="width:52px; height:52px; border-radius:0.375rem; background:#eef2ff; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                            <i class="fab fa-facebook" style="color:#a5b4fc;"></i>
                        </div>
                        @endif
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:0.8125rem; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                {{ $p->message ?: '(Aucun texte)' }}
                            </div>
                            <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.2rem;">
                                {{ $p->posted_at?->diffForHumans() ?? '—' }}
                                @if($p->page) · {{ $p->page->page_name }} @endif
                            </div>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('post_id')
                <div class="invalid-feedback" style="display:block; margin-top:0.5rem;">{{ $message }}</div>
                @enderror
                @else
                <div style="text-align:center; padding:2rem; color:#94a3b8;">
                    <i class="fab fa-facebook" style="font-size:2rem; display:block; margin-bottom:0.5rem; color:#e2e8f0;"></i>
                    Aucun post boostable disponible.
                    <a href="{{ route('posts.index') }}" style="color:var(--color-primary);">Synchroniser les posts</a>
                </div>
                @endif
            </div>
        </div>

        @endif

        {{-- ── Budget ── --}}
        <div class="card" style="margin-bottom:1.25rem;">
            <div class="card-header">
                <i class="fas fa-coins" style="color:var(--color-primary);"></i>
                Budget total de la campagne
            </div>
            <div class="card-body">
                <input type="hidden" name="budget_value" id="budget_value" value="{{ old('budget_value', 7000) }}">

                <div style="display:flex; flex-wrap:wrap; gap:0.625rem; margin-bottom:1rem;" id="budgetPills">
                    @foreach([3500, 7000, 14000, 35000, 70000] as $b)
                    <button type="button"
                            onclick="selectBudget({{ $b }})"
                            data-budget="{{ $b }}"
                            style="padding:0.5rem 1rem; border-radius:9999px; border:2px solid {{ old('budget_value', 7000) == $b ? 'var(--color-primary)' : 'var(--color-border)' }};
                                   background:{{ old('budget_value', 7000) == $b ? 'var(--color-primary-light)' : '#fff' }};
                                   color:{{ old('budget_value', 7000) == $b ? 'var(--color-primary)' : '#374151' }};
                                   font-weight:{{ old('budget_value', 7000) == $b ? '700' : '500' }};
                                   font-size:0.875rem; cursor:pointer; transition:all .15s;">
                        {{ number_format($b) }} FCFA
                    </button>
                    @endforeach
                </div>

                <div style="display:flex; align-items:center; gap:0.625rem;">
                    <input type="number"
                           id="budget_custom"
                           placeholder="Autre montant…"
                           min="500"
                           step="500"
                           oninput="selectBudget(this.value ? parseInt(this.value) : null, true)"
                           style="flex:1; padding:0.5rem 0.75rem; border:2px solid var(--color-border); border-radius:0.5rem; font-size:0.875rem; outline:none;"
                           onfocus="this.style.borderColor='var(--color-primary)'"
                           onblur="if(!this.value)this.style.borderColor='var(--color-border)'">
                    <span style="font-size:0.875rem; color:#64748b; white-space:nowrap;">FCFA</span>
                </div>
                @error('budget_value')
                <div class="invalid-feedback" style="display:block; margin-top:0.375rem;">{{ $message }}</div>
                @enderror
                <p style="margin:0.625rem 0 0; font-size:0.8125rem; color:#94a3b8;">
                    <i class="fas fa-info-circle" style="margin-right:0.25rem;"></i>
                    Budget total pour toute la durée (lifetime budget).
                </p>
            </div>
        </div>

        {{-- ── Durée ── --}}
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <i class="fas fa-calendar-alt" style="color:var(--color-primary);"></i>
                Durée de diffusion
            </div>
            <div class="card-body">
                <div style="display:flex; flex-wrap:wrap; gap:0.625rem; margin-bottom:0.875rem;" id="durationPills">
                    @foreach([1 => '1 jour', 3 => '3 jours', 7 => '7 jours', 14 => '14 jours', 30 => '30 jours'] as $d => $label)
                    <button type="button"
                            onclick="selectDuration({{ $d }})"
                            data-duration="{{ $d }}"
                            style="padding:0.5rem 1rem; border-radius:9999px; border:2px solid {{ old('duration_days', 7) == $d ? 'var(--color-primary)' : 'var(--color-border)' }};
                                   background:{{ old('duration_days', 7) == $d ? 'var(--color-primary-light)' : '#fff' }};
                                   color:{{ old('duration_days', 7) == $d ? 'var(--color-primary)' : '#374151' }};
                                   font-weight:{{ old('duration_days', 7) == $d ? '700' : '500' }};
                                   font-size:0.875rem; cursor:pointer; transition:all .15s;">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
                <input type="hidden" name="duration_days" id="duration_days" value="{{ old('duration_days', 7) }}">
                @error('duration_days')
                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                @enderror

                <div style="padding:0.75rem 1rem; background:#f8fafc; border-radius:0.5rem; font-size:0.875rem; color:#64748b;">
                    Budget journalier estimé :
                    <strong id="dailyBudget" style="color:var(--color-primary);">1 000 FCFA/j</strong>
                </div>
            </div>
        </div>

        {{-- ── Actions ── --}}
        <div style="display:flex; gap:0.75rem; justify-content:flex-end;">
            <a href="{{ route('posts.index') }}" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
            <button type="submit" class="btn-primary" id="submitBtn">
                <i class="fas fa-rocket"></i>
                Lancer le boost
            </button>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
let currentBudget   = {{ old('budget_value', 7000) }};
let currentDuration = {{ old('duration_days', 7) }};

function selectBudget(val, fromCustom) {
    if (!val || val < 500) return;
    currentBudget = val;
    document.getElementById('budget_value').value = val;

    document.querySelectorAll('#budgetPills button').forEach(function(btn) {
        var active = parseInt(btn.dataset.budget) === val;
        btn.style.border     = '2px solid ' + (active ? 'var(--color-primary)' : 'var(--color-border)');
        btn.style.background = active ? 'var(--color-primary-light)' : '#fff';
        btn.style.color      = active ? 'var(--color-primary)' : '#374151';
        btn.style.fontWeight = active ? '700' : '500';
    });

    if (!fromCustom) document.getElementById('budget_custom').value = '';
    updateSummary();
}

function selectDuration(val) {
    currentDuration = val;
    document.getElementById('duration_days').value = val;

    document.querySelectorAll('#durationPills button').forEach(function(btn) {
        var active = parseInt(btn.dataset.duration) === val;
        btn.style.border     = '2px solid ' + (active ? 'var(--color-primary)' : 'var(--color-border)');
        btn.style.background = active ? 'var(--color-primary-light)' : '#fff';
        btn.style.color      = active ? 'var(--color-primary)' : '#374151';
        btn.style.fontWeight = active ? '700' : '500';
    });

    updateSummary();
}

function updateSummary() {
    if (currentBudget && currentDuration) {
        var daily = Math.round(currentBudget / currentDuration);
        document.getElementById('dailyBudget').textContent =
            new Intl.NumberFormat('fr-FR').format(daily) + ' FCFA/j';
    }
}

document.getElementById('boostForm').addEventListener('submit', function() {
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Lancement…';
});

updateSummary();
</script>
@endpush
