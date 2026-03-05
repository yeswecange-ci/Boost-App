@extends('layouts.app')

@section('page-title', 'Dashboard')
@php
    $_role = auth()->user()->roles->first()?->name ?? '';
    $_subtitle = match(true) {
        in_array($_role, ['validator_n1','validator']) => 'Files de validation — Boosts & Campagnes',
        $_role === 'validator_n2'                      => 'Validation finale N+2 — Boosts & Campagnes',
        $_role === 'admin'                             => 'Vue administrative globale',
        default                                        => 'Vue d\'ensemble de vos activités',
    };
@endphp
@section('page-subtitle', $_subtitle)

@section('content')

{{-- KPI Cards --}}
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:2rem;">

    {{-- Total boosts --}}
    <div class="stat-card">
        <div class="stat-icon" style="background:#eef2ff; color:#4f46e5;">
            <i class="fas fa-rocket"></i>
        </div>
        <div>
            <div class="stat-value">{{ $totalBoosts }}</div>
            <div class="stat-label">Total boosts</div>
        </div>
    </div>

    {{-- En attente --}}
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3; color:#854d0e;">
            <i class="fas fa-clock"></i>
        </div>
        <div>
            <div class="stat-value">{{ $pendingCount }}</div>
            <div class="stat-label">En attente</div>
        </div>
    </div>

    {{-- Actifs --}}
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7; color:#15803d;">
            <i class="fas fa-play-circle"></i>
        </div>
        <div>
            <div class="stat-value">{{ $activeCount }}</div>
            <div class="stat-label">Campagnes actives</div>
        </div>
    </div>

    {{-- Budget par devise --}}
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e8ff; color:#7c3aed;">
            <i class="fas fa-coins"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size:1rem; line-height:1.4;">
                @forelse($budgetByCurrency as $currency => $total)
                <div>{{ number_format($total, 0, ',', ' ') }} <span style="font-size:0.75rem; font-weight:400; color:#94a3b8;">{{ $currency }}</span></div>
                @empty
                <div style="color:#94a3b8;">—</div>
                @endforelse
            </div>
            <div class="stat-label">Budget approuvé</div>
        </div>
    </div>

</div>

{{-- Monitoring synchro (admins/validators uniquement) --}}
@if($isValidator && $lastSyncRun)
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header" style="justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-sync-alt" style="color:var(--color-primary);"></i>
            Dernière synchronisation Facebook
        </div>
        <a href="{{ route('sync-runs.index') }}" class="btn-secondary btn-sm">
            <i class="fas fa-list"></i>
            Voir tous les runs
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
                <div style="font-size:1.5rem; {{ $syncStyle }}">
                    <i class="fas fa-{{ $syncIcon }}"></i>
                </div>
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

{{-- Section Campagnes Media Buyer --}}
@if($campaignCounts['total'] > 0 || $isValidator)
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header" style="justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:.5rem;">
            <i class="fas fa-layer-group" style="color:var(--color-primary);"></i>
            Campagnes Media Buyer
        </div>
        <a href="{{ route('campaigns.index') }}" class="btn-secondary btn-sm">
            <i class="fas fa-arrow-right"></i> Voir tout
        </a>
    </div>
    <div class="card-body">

        @if($isValidator)
        {{-- Validateurs : files d'attente avec CTAs --}}
        <div style="display:flex; flex-wrap:wrap; gap:1rem;">
            @if($isN1 && $campaignCounts['pending_n1'] > 0)
            <a href="{{ route('campaigns.pending') }}"
               style="display:flex; align-items:center; gap:.875rem; padding:1rem 1.25rem; background:#fef9c3; border:1.5px solid #fde68a; border-radius:.625rem; text-decoration:none; flex:1; min-width:180px;">
                <i class="fas fa-clock" style="font-size:1.5rem; color:#92400e;"></i>
                <div>
                    <div style="font-size:1.5rem; font-weight:800; color:#92400e; line-height:1;">{{ $campaignCounts['pending_n1'] }}</div>
                    <div style="font-size:.75rem; color:#92400e; font-weight:500; margin-top:.125rem;">Campagne(s) — validation N+1</div>
                </div>
                <i class="fas fa-arrow-right" style="margin-left:auto; color:#92400e; font-size:.875rem;"></i>
            </a>
            @endif

            @if($isN2 && $campaignCounts['pending_n2'] > 0)
            <a href="{{ route('campaigns.pending') }}"
               style="display:flex; align-items:center; gap:.875rem; padding:1rem 1.25rem; background:#dbeafe; border:1.5px solid #93c5fd; border-radius:.625rem; text-decoration:none; flex:1; min-width:180px;">
                <i class="fas fa-check-circle" style="font-size:1.5rem; color:#1d4ed8;"></i>
                <div>
                    <div style="font-size:1.5rem; font-weight:800; color:#1d4ed8; line-height:1;">{{ $campaignCounts['pending_n2'] }}</div>
                    <div style="font-size:.75rem; color:#1d4ed8; font-weight:500; margin-top:.125rem;">Campagne(s) — validation N+2</div>
                </div>
                <i class="fas fa-arrow-right" style="margin-left:auto; color:#1d4ed8; font-size:.875rem;"></i>
            </a>
            @endif

            @if((!$isN1 || $campaignCounts['pending_n1'] === 0) && (!$isN2 || $campaignCounts['pending_n2'] === 0))
            <div style="display:flex; align-items:center; gap:.625rem; color:var(--color-muted); font-size:.875rem; padding:.5rem 0;">
                <i class="fas fa-check-circle" style="color:#86efac; font-size:1.25rem;"></i>
                Aucune campagne en attente de validation.
            </div>
            @endif

            {{-- Aperçu global pour admin --}}
            @if($isN1 && $isN2 && $campaignCounts['total'] > 0)
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:.5rem; width:100%; margin-top:.25rem;">
                @foreach([
                    ['label'=>'Total',    'val'=>$campaignCounts['total'], 'color'=>'#4f46e5'],
                    ['label'=>'Boostées', 'val'=>$campaignCounts['done'],  'color'=>'#15803d'],
                    ['label'=>'Erreurs',  'val'=>$campaignCounts['error'], 'color'=>'#b91c1c'],
                ] as $s)
                <div style="text-align:center; padding:.5rem; background:#f8fafc; border-radius:.5rem;">
                    <div style="font-size:1.25rem; font-weight:700; color:{{ $s['color'] }};">{{ $s['val'] }}</div>
                    <div style="font-size:.6875rem; color:var(--color-muted);">{{ $s['label'] }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        @else
        {{-- Opérateurs : progression de leurs campagnes --}}
        @if($campaignCounts['total'] === 0)
        <div style="text-align:center; padding:1.5rem 1rem; color:var(--color-muted);">
            <i class="fas fa-layer-group" style="font-size:2rem; color:#e2e8f0; display:block; margin-bottom:.75rem;"></i>
            <div style="font-size:.875rem;">Aucune campagne créée. <a href="{{ route('campaigns.create') }}" style="color:var(--color-primary); font-weight:500;">Créer maintenant</a></div>
        </div>
        @else
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(110px, 1fr)); gap:.75rem;">
            @foreach([
                ['label'=>'Total',          'val'=>$campaignCounts['total'],                                      'color'=>'#4f46e5', 'bg'=>'#eef2ff'],
                ['label'=>'Brouillons',     'val'=>$campaignCounts['draft'],                                      'color'=>'#64748b', 'bg'=>'#f1f5f9'],
                ['label'=>'En validation',  'val'=>$campaignCounts['pending_n1']+$campaignCounts['pending_n2'],   'color'=>'#854d0e', 'bg'=>'#fef9c3'],
                ['label'=>'Approuvées',     'val'=>$campaignCounts['approved'],                                   'color'=>'#0369a1', 'bg'=>'#e0f2fe'],
                ['label'=>'Boostées',       'val'=>$campaignCounts['done'],                                       'color'=>'#15803d', 'bg'=>'#dcfce7'],
                ['label'=>'Erreurs',        'val'=>$campaignCounts['error'],                                      'color'=>'#b91c1c', 'bg'=>'#fee2e2'],
            ] as $s)
            <div style="background:{{ $s['bg'] }}; border-radius:.5rem; padding:.625rem .75rem; text-align:center;">
                <div style="font-size:1.375rem; font-weight:700; color:{{ $s['color'] }};">{{ $s['val'] }}</div>
                <div style="font-size:.6875rem; color:#64748b; margin-top:.125rem; white-space:nowrap;">{{ $s['label'] }}</div>
            </div>
            @endforeach
        </div>
        <div style="margin-top:.875rem; text-align:right;">
            <a href="{{ route('campaigns.create') }}" class="btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nouvelle campagne
            </a>
        </div>
        @endif
        @endif

    </div>
</div>
@endif

{{-- Recent boosts table --}}
<div class="card">
    <div class="card-header" style="justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-history" style="color:var(--color-primary);"></i>
            Derniers boosts
        </div>
        @if($isValidator)
        {{-- Les validateurs voient tous les boosts, pas de création --}}
        @else
        <a href="{{ route('posts.index') }}" class="btn-primary btn-sm">
            <i class="fas fa-plus"></i>
            Nouveau boost
        </a>
        @endif
    </div>

    @if($recentBoosts->isEmpty())
    <div class="card-body" style="text-align:center; padding: 3rem 1.25rem; color:#64748b;">
        <i class="fas fa-rocket" style="font-size:2.5rem; color:#e2e8f0; display:block; margin-bottom:1rem;"></i>
        <div style="font-weight:500; margin-bottom:0.375rem;">Aucun boost pour l'instant</div>
        <div style="font-size:0.875rem;">Commencez par booster un post Facebook !</div>
        <a href="{{ route('posts.index') }}" class="btn-primary" style="display:inline-flex; margin-top:1rem;">
            <i class="fas fa-rocket"></i>
            Voir les posts
        </a>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Page / Post</th>
                    @if($isValidator)
                    <th>Opérateur</th>
                    @endif
                    <th>Période</th>
                    <th>Budget</th>
                    <th>Statut</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentBoosts as $boost)
                <tr>
                    <td style="color:#94a3b8; font-size:0.8125rem;">#{{ $boost->id }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:0.625rem;">
                            @if($boost->post_thumbnail)
                            <img src="{{ $boost->post_thumbnail }}" style="width:40px; height:40px; object-fit:cover; border-radius:0.375rem; flex-shrink:0;">
                            @else
                            <div style="width:40px; height:40px; background:#f1f5f9; border-radius:0.375rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="fab fa-facebook" style="color:#cbd5e1;"></i>
                            </div>
                            @endif
                            <div>
                                <div style="font-weight:500; font-size:0.875rem;">{{ $boost->page_name }}</div>
                                <div style="font-size:0.75rem; color:#64748b;">{{ Str::limit($boost->post_message, 45) }}</div>
                            </div>
                        </div>
                    </td>
                    @if($isValidator)
                    <td style="font-size:0.875rem; color:#374151;">{{ $boost->operator?->name ?? '(supprimé)' }}</td>
                    @endif
                    <td>
                        <div style="font-size:0.8125rem;">{{ $boost->start_date->format('d/m/Y') }}</div>
                        <div style="font-size:0.8125rem; color:#94a3b8;">{{ $boost->end_date->format('d/m/Y') }}</div>
                    </td>
                    <td style="font-weight:600; color:var(--color-primary); font-size:0.875rem;">
                        {{ number_format($boost->budget, 0, ',', ' ') }}
                        <span style="font-size:0.75rem; font-weight:400; color:#94a3b8;">{{ $boost->currency }}</span>
                    </td>
                    <td>
                        @php
                        $statusMap = [
                            'draft'        => ['label'=>'Brouillon',      'class'=>'badge-status-draft'],
                            'pending_n1'   => ['label'=>'Attente N+1',    'class'=>'badge-status-pending'],
                            'pending_n2'   => ['label'=>'Attente N+2',    'class'=>'badge-status-pending'],
                            'rejected_n1'  => ['label'=>'Rejeté N+1',     'class'=>'badge-status-rejected'],
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
                        $s = $statusMap[$boost->status] ?? ['label'=>ucfirst($boost->status), 'class'=>'badge-status-draft'];
                        @endphp
                        <span class="badge-status {{ $s['class'] }}">{{ $s['label'] }}</span>
                    </td>
                    <td>
                        <a href="{{ route('boost.show', $boost->id) }}" class="btn-secondary btn-sm">
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
        <a href="{{ route('boost.all') }}" style="font-size:0.875rem; color:var(--color-primary); text-decoration:none; font-weight:500;">
            Voir tous les boosts <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i>
        </a>
        @else
        <a href="{{ route('boost.my-requests') }}" style="font-size:0.875rem; color:var(--color-primary); text-decoration:none; font-weight:500;">
            Voir tous mes boosts <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i>
        </a>
        @endif
    </div>
    @endif
</div>

@endsection
