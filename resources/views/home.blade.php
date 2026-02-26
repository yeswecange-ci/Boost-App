@extends('layouts.app')

@section('page-title', 'Dashboard')
@section('page-subtitle', 'Vue d\'ensemble de vos activités de boost')

@section('content')

{{-- KPI Cards --}}
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:2rem;">

    {{-- Total boosts --}}
    <div class="stat-card">
        <div class="stat-icon" style="background:#eef2ff; color:#4f46e5;">
            <i class="fas fa-rocket"></i>
        </div>
        <div>
            <div class="stat-value">
                {{ \App\Models\BoostRequest::when(!auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']), fn($q) => $q->where('operator_id', auth()->id()))->count() }}
            </div>
            <div class="stat-label">Total boosts</div>
        </div>
    </div>

    {{-- En attente --}}
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3; color:#854d0e;">
            <i class="fas fa-clock"></i>
        </div>
        <div>
            <div class="stat-value">
                {{ \App\Models\BoostRequest::when(!auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']), fn($q) => $q->where('operator_id', auth()->id()))->whereIn('status',['pending_n1','pending_n2'])->count() }}
            </div>
            <div class="stat-label">En attente</div>
        </div>
    </div>

    {{-- Actifs --}}
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7; color:#15803d;">
            <i class="fas fa-play-circle"></i>
        </div>
        <div>
            <div class="stat-value">
                {{ \App\Models\BoostRequest::when(!auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']), fn($q) => $q->where('operator_id', auth()->id()))->where('status','active')->count() }}
            </div>
            <div class="stat-label">Campagnes actives</div>
        </div>
    </div>

    {{-- Budget total --}}
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e8ff; color:#7c3aed;">
            <i class="fas fa-coins"></i>
        </div>
        <div>
            <div class="stat-value" style="font-size:1.125rem;">
                @php
                    $totalBudget = \App\Models\BoostRequest::when(!auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']), fn($q) => $q->where('operator_id', auth()->id()))
                        ->whereIn('status', ['approved','paused_ready','active','completed'])
                        ->sum('budget');
                @endphp
                {{ number_format($totalBudget, 0, ',', ' ') }} XOF
            </div>
            <div class="stat-label">Budget total approuvé</div>
        </div>
    </div>

</div>

{{-- Recent boosts table --}}
<div class="card">
    <div class="card-header" style="justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-history" style="color:var(--color-primary);"></i>
            Derniers boosts
        </div>
        <a href="{{ route('posts.index') }}" class="btn-primary btn-sm">
            <i class="fas fa-plus"></i>
            Nouveau boost
        </a>
    </div>

    @php
        $recentBoosts = \App\Models\BoostRequest::with('operator')
            ->when(!auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']), fn($q) => $q->where('operator_id', auth()->id()))
            ->latest()
            ->take(5)
            ->get();
    @endphp

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
                    @if(auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']))
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
                    @if(auth()->user()->hasRole(['validator_n1','validator_n2','validator','admin']))
                    <td style="font-size:0.875rem; color:#374151;">{{ $boost->operator->name }}</td>
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
        <a href="{{ route('boost.my-requests') }}" style="font-size:0.875rem; color:var(--color-primary); text-decoration:none; font-weight:500;">
            Voir tous mes boosts <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i>
        </a>
    </div>
    @endif
</div>

@endsection
