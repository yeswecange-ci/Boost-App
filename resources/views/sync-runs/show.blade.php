@extends('layouts.app')

@section('page-title', 'Run de synchronisation #' . $syncRun->id)
@section('page-subtitle', 'Page ' . $syncRun->page_id . ' — ' . $syncRun->started_at->format('d/m/Y H:i:s'))

@section('content')

<div style="display:flex; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; align-items:center;">
    <a href="{{ route('sync-runs.index') }}" class="btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i>
        Retour à la liste
    </a>

    @php
    $statusStyle = match($syncRun->status) {
        'FINISHED' => 'background:#dcfce7; color:#15803d;',
        'FAILED'   => 'background:#fee2e2; color:#b91c1c;',
        'RUNNING'  => 'background:#fef9c3; color:#854d0e;',
        default    => 'background:#f1f5f9; color:#64748b;',
    };
    @endphp
    <span style="padding:0.375rem 1rem; border-radius:9999px; font-size:0.875rem; font-weight:600; {{ $statusStyle }}">
        {{ $syncRun->status }}
    </span>

    @if($syncRun->duration)
    <span style="font-size:0.875rem; color:#64748b;">
        <i class="far fa-clock" style="margin-right:0.25rem;"></i>
        Durée : {{ $syncRun->duration }}
    </span>
    @endif
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">

    {{-- Infos du run --}}
    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle" style="color:var(--color-primary);"></i>
            Informations du run
        </div>
        <div class="card-body">
            <dl style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem 1rem; font-size:0.875rem;">
                <dt style="color:#64748b; font-weight:500;">ID</dt>
                <dd style="margin:0;">#{{ $syncRun->id }}</dd>

                <dt style="color:#64748b; font-weight:500;">Source</dt>
                <dd style="margin:0;">{{ $syncRun->source }}</dd>

                <dt style="color:#64748b; font-weight:500;">Page ID</dt>
                <dd style="margin:0; font-family:monospace;">{{ $syncRun->page_id }}</dd>

                <dt style="color:#64748b; font-weight:500;">Démarré</dt>
                <dd style="margin:0;">{{ $syncRun->started_at->format('d/m/Y H:i:s') }}</dd>

                <dt style="color:#64748b; font-weight:500;">Terminé</dt>
                <dd style="margin:0;">{{ $syncRun->finished_at?->format('d/m/Y H:i:s') ?? '—' }}</dd>

                <dt style="color:#64748b; font-weight:500;">Note</dt>
                <dd style="margin:0; color:#dc2626;">{{ $syncRun->note ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="card">
        <div class="card-header">
            <i class="fas fa-chart-bar" style="color:var(--color-primary);"></i>
            Résumé
        </div>
        <div class="card-body">
            <dl style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem 1rem; font-size:0.875rem;">
                <dt style="color:#64748b; font-weight:500;">Posts modifiés</dt>
                <dd style="margin:0; font-weight:600;">{{ $changedPosts->count() }}</dd>

                <dt style="color:#64748b; font-weight:500;">Erreurs API</dt>
                <dd style="margin:0; font-weight:600; color:{{ $syncRun->errors->count() > 0 ? '#dc2626' : '#15803d' }};">
                    {{ $syncRun->errors->count() }}
                </dd>
            </dl>
        </div>
    </div>
</div>

{{-- Erreurs --}}
@if($syncRun->errors->isNotEmpty())
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header" style="background:#fef2f2;">
        <i class="fas fa-exclamation-triangle" style="color:#b91c1c;"></i>
        <span style="color:#b91c1c; font-weight:600;">Erreurs ({{ $syncRun->errors->count() }})</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Post ID</th>
                    <th>Étape</th>
                    <th>Code erreur</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($syncRun->errors as $error)
                <tr>
                    <td style="font-family:monospace; font-size:0.8125rem;">{{ $error->post_id ?? '—' }}</td>
                    <td>
                        <span style="background:#fee2e2; color:#b91c1c; padding:0.2rem 0.5rem; border-radius:0.25rem; font-size:0.75rem;">
                            {{ $error->step }}
                        </span>
                    </td>
                    <td style="font-family:monospace; font-size:0.8125rem; color:#dc2626;">{{ $error->error_code ?? '—' }}</td>
                    <td style="font-size:0.8125rem; color:#374151; max-width:350px;">{{ $error->error_message }}</td>
                    <td style="font-size:0.8125rem; color:#94a3b8;">{{ $error->created_at->format('H:i:s') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Posts modifiés (SCD2) --}}
@if($changedPosts->isNotEmpty())
<div class="card">
    <div class="card-header">
        <i class="fas fa-file-alt" style="color:var(--color-primary);"></i>
        Posts versionnés lors de ce run ({{ $changedPosts->count() }})
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Post ID</th>
                    <th>Type</th>
                    <th>Message</th>
                    <th>Statut FB</th>
                    <th>Hash</th>
                    <th>Actif</th>
                    <th>Valide depuis</th>
                </tr>
            </thead>
            <tbody>
                @foreach($changedPosts as $postMasterId => $versions)
                @foreach($versions as $version)
                <tr>
                    <td style="font-family:monospace; font-size:0.75rem;">
                        {{ $version->postMaster?->post_id ?? '#' . $postMasterId }}
                    </td>
                    <td style="font-size:0.8125rem;">{{ $version->type ?? '—' }}</td>
                    <td style="font-size:0.8125rem; max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ Str::limit($version->message ?? '—', 60) }}
                    </td>
                    <td>
                        @php $fbStatus = $version->postMaster?->fb_status ?? 'FB_OK'; @endphp
                        <span style="padding:0.2rem 0.5rem; border-radius:9999px; font-size:0.7rem; font-weight:600;
                            {{ $fbStatus === 'FB_OK' ? 'background:#dcfce7; color:#15803d;' : 'background:#fee2e2; color:#b91c1c;' }}">
                            {{ $fbStatus }}
                        </span>
                    </td>
                    <td style="font-family:monospace; font-size:0.7rem; color:#94a3b8;">
                        {{ substr($version->row_hash, 0, 12) }}…
                    </td>
                    <td>
                        <span style="font-size:0.75rem; {{ $version->is_active ? 'color:#15803d; font-weight:600;' : 'color:#94a3b8;' }}">
                            {{ $version->is_active ? 'Oui' : 'Non' }}
                        </span>
                    </td>
                    <td style="font-size:0.8125rem; color:#64748b;">
                        {{ $version->valid_from?->format('H:i:s') ?? '—' }}
                    </td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card">
    <div class="card-body" style="text-align:center; padding:2rem; color:#94a3b8;">
        <i class="fas fa-equals" style="font-size:1.5rem; display:block; margin-bottom:0.5rem; color:#e2e8f0;"></i>
        Aucun post n'a changé lors de ce run (hashes identiques).
    </div>
</div>
@endif

@endsection
