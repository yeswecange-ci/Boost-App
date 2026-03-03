@extends('layouts.app')

@section('page-title', 'Synchronisations Facebook')
@section('page-subtitle', 'Historique des runs de synchronisation et état des posts')

@section('content')

{{-- KPI rapides --}}
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; margin-bottom:2rem;">

    <div class="stat-card">
        <div class="stat-icon" style="background:#eef2ff; color:#4f46e5;">
            <i class="fas fa-sync-alt"></i>
        </div>
        <div>
            <div class="stat-value">{{ $runs->total() }}</div>
            <div class="stat-label">Total runs</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7; color:#15803d;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div>
            <div class="stat-value">{{ $runs->getCollection()->where('status', 'FINISHED')->count() }}</div>
            <div class="stat-label">Réussis (page courante)</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2; color:#b91c1c;">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div>
            <div class="stat-value">{{ $runs->getCollection()->where('status', 'FAILED')->count() }}</div>
            <div class="stat-label">Échoués (page courante)</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3; color:#854d0e;">
            <i class="fas fa-ban"></i>
        </div>
        <div>
            <div class="stat-value">{{ $nonBoostableCount }}</div>
            <div class="stat-label">Posts non boostables</div>
        </div>
    </div>

</div>

{{-- Tableau des runs --}}
<div class="card">
    <div class="card-header">
        <div style="display:flex; align-items:center; gap:0.5rem;">
            <i class="fas fa-history" style="color:var(--color-primary);"></i>
            Historique des synchronisations
        </div>
    </div>

    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Page ID</th>
                    <th>Source</th>
                    <th>Statut</th>
                    <th>Démarré</th>
                    <th>Durée</th>
                    <th>Erreurs</th>
                    <th>Note</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($runs as $run)
                <tr>
                    <td style="color:#94a3b8; font-size:0.8125rem;">#{{ $run->id }}</td>
                    <td style="font-size:0.8125rem; font-family:monospace;">{{ $run->page_id }}</td>
                    <td style="font-size:0.8125rem;">{{ $run->source }}</td>
                    <td>
                        @php
                        $statusStyle = match($run->status) {
                            'FINISHED' => 'background:#dcfce7; color:#15803d;',
                            'FAILED'   => 'background:#fee2e2; color:#b91c1c;',
                            'RUNNING'  => 'background:#fef9c3; color:#854d0e;',
                            default    => 'background:#f1f5f9; color:#64748b;',
                        };
                        $statusIcon = match($run->status) {
                            'FINISHED' => 'check-circle',
                            'FAILED'   => 'times-circle',
                            'RUNNING'  => 'spinner fa-spin',
                            default    => 'circle',
                        };
                        @endphp
                        <span style="padding:0.2rem 0.625rem; border-radius:9999px; font-size:0.75rem; font-weight:600; {{ $statusStyle }}">
                            <i class="fas fa-{{ $statusIcon }}" style="margin-right:0.25rem;"></i>
                            {{ $run->status }}
                        </span>
                    </td>
                    <td style="font-size:0.8125rem;">
                        {{ $run->started_at->format('d/m/Y H:i:s') }}
                    </td>
                    <td style="font-size:0.8125rem; color:#64748b;">
                        {{ $run->duration ?? '—' }}
                    </td>
                    <td>
                        @if($run->errors_count > 0)
                        <span style="background:#fee2e2; color:#b91c1c; padding:0.2rem 0.5rem; border-radius:9999px; font-size:0.75rem; font-weight:600;">
                            {{ $run->errors_count }} erreur(s)
                        </span>
                        @else
                        <span style="color:#94a3b8; font-size:0.8125rem;">—</span>
                        @endif
                    </td>
                    <td style="font-size:0.8125rem; color:#64748b; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $run->note ?? '—' }}
                    </td>
                    <td>
                        <a href="{{ route('sync-runs.show', $run->id) }}" class="btn-secondary btn-sm">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:3rem; color:#94a3b8;">
                        <i class="fas fa-sync-alt" style="font-size:2rem; display:block; margin-bottom:0.75rem; color:#e2e8f0;"></i>
                        Aucune synchronisation enregistrée
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($runs->hasPages())
    <div class="card-footer">
        {{ $runs->links() }}
    </div>
    @endif
</div>

@endsection
