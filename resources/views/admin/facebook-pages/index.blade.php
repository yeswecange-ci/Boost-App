@extends('layouts.app')

@section('page-title', 'Pages Facebook')
@section('page-subtitle', 'Gérez les pages Facebook connectées à Boost Manager')

@section('content')

{{-- Formulaires POST séparés (pas imbriquables) --}}
<form id="form-import" method="POST" action="{{ route('admin.facebook-pages.import') }}" style="display:none;">@csrf</form>
@foreach($pages as $p)
<form id="form-toggle-{{ $p->id }}" method="POST" action="{{ route('admin.facebook-pages.toggle', $p) }}" style="display:none;">@csrf</form>
@endforeach

<div x-data="{ showAdd: {{ $errors->hasAny(['page_id','page_name','access_token']) ? 'true' : 'false' }} }">

    {{-- ── Barre d'actions ── --}}
    <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap;">
        <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
            {{-- Import Meta --}}
            <button type="submit" form="form-import" class="btn-primary">
                <i class="fab fa-facebook" style="margin-right:0.5rem;"></i>
                Importer depuis Meta
            </button>
            {{-- Ajout manuel --}}
            <button type="button" @click="showAdd = !showAdd" class="btn-secondary">
                <i class="fas fa-plus" style="margin-right:0.5rem;"></i>
                Ajouter manuellement
            </button>
        </div>
        <span style="font-size:0.8125rem; color:#64748b;">
            {{ $pages->count() }} page(s) — {{ $pages->where('is_active', true)->count() }} active(s)
        </span>
    </div>

    {{-- ── Formulaire ajout manuel (slide) ── --}}
    <div x-show="showAdd" style="display:none; margin-bottom:1.5rem;">
        <div class="card">
            <div class="card-header">
                <h3 style="font-size:0.9375rem; font-weight:600; color:#0f172a; margin:0;">
                    <i class="fas fa-plus-circle" style="margin-right:0.5rem; color:var(--color-primary);"></i>
                    Ajouter une page manuellement
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.facebook-pages.store') }}">
                    @csrf
                    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem; margin-bottom:1rem;">
                        <div>
                            <label class="form-label">Facebook Page ID <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="page_id" class="form-control @error('page_id') is-invalid @enderror"
                                   value="{{ old('page_id') }}" placeholder="ex: 123456789012345" required>
                            @error('page_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label">Nom de la page <span style="color:#ef4444;">*</span></label>
                            <input type="text" name="page_name" class="form-control @error('page_name') is-invalid @enderror"
                                   value="{{ old('page_name') }}" placeholder="ex: Bracongo CI" required>
                            @error('page_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label">Compte publicitaire (Act ID)</label>
                            <input type="text" name="ad_account_id" class="form-control"
                                   value="{{ old('ad_account_id') }}" placeholder="ex: act_1234567890">
                        </div>
                        <div>
                            <label class="form-label">Compte Instagram (optionnel)</label>
                            <input type="text" name="instagram_account_id" class="form-control"
                                   value="{{ old('instagram_account_id') }}" placeholder="ID Instagram">
                        </div>
                    </div>
                    <div style="margin-bottom:1rem;">
                        <label class="form-label">Page Access Token <span style="color:#ef4444;">*</span></label>
                        <textarea name="access_token" rows="2"
                                  class="form-control @error('access_token') is-invalid @enderror"
                                  placeholder="EAAxxxxx..." required>{{ old('access_token') }}</textarea>
                        @error('access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div style="display:flex; gap:0.75rem;">
                        <button type="submit" class="btn-primary btn-sm">
                            <i class="fas fa-save" style="margin-right:0.375rem;"></i>Enregistrer
                        </button>
                        <button type="button" @click="showAdd = false" class="btn-secondary btn-sm">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Tableau des pages ── --}}
    <div class="card">
        <div class="card-body" style="padding:0;">
            @if($pages->isEmpty())
            <div style="padding:3rem; text-align:center; color:#64748b;">
                <i class="fab fa-facebook-square" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:1rem; display:block;"></i>
                Aucune page Facebook enregistrée.<br>
                <span style="font-size:0.875rem;">Cliquez sur "Importer depuis Meta" ou "Ajouter manuellement".</span>
            </div>
            @else
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Facebook ID</th>
                            <th>Compte pub</th>
                            <th style="text-align:center;">Posts</th>
                            <th>Dernière sync</th>
                            <th style="text-align:center;">Statut</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pages as $page)
                        @php $lastSync = $lastSyncs[$page->page_id] ?? null; @endphp
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:0.625rem;">
                                    <div style="width:34px; height:34px; border-radius:8px; background:#1877f2; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <i class="fab fa-facebook-f" style="color:#fff; font-size:0.9rem;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:600; font-size:0.875rem; color:#0f172a;">{{ $page->page_name }}</div>
                                        @if($page->instagram_account_id)
                                        <div style="font-size:0.75rem; color:#94a3b8;">
                                            <i class="fab fa-instagram" style="margin-right:0.25rem;"></i>{{ $page->instagram_account_id }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code style="font-size:0.8125rem; background:#f1f5f9; padding:0.125rem 0.375rem; border-radius:4px; color:#475569;">
                                    {{ $page->page_id }}
                                </code>
                            </td>
                            <td>
                                @if($page->ad_account_id)
                                <code style="font-size:0.8125rem; background:#f1f5f9; padding:0.125rem 0.375rem; border-radius:4px; color:#475569;">
                                    {{ $page->ad_account_id }}
                                </code>
                                @else
                                <span style="color:#cbd5e1; font-size:0.8125rem;">—</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                <span style="font-weight:600; color:#0f172a;">{{ number_format($page->posts_count) }}</span>
                            </td>
                            <td>
                                @if($lastSync)
                                <div style="font-size:0.8125rem; color:#475569;">
                                    {{ $lastSync->finished_at->diffForHumans() }}
                                </div>
                                <div style="font-size:0.75rem; color:#94a3b8;">
                                    {{ $lastSync->finished_at->format('d/m/Y H:i') }}
                                </div>
                                @else
                                <span style="font-size:0.8125rem; color:#cbd5e1;">Jamais synchronisée</span>
                                @endif
                            </td>
                            <td style="text-align:center;">
                                @if($page->is_active)
                                <span class="badge-status-active">Active</span>
                                @else
                                <span class="badge-status-draft">Inactive</span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                <div style="display:flex; align-items:center; justify-content:flex-end; gap:0.5rem;">
                                    <a href="{{ route('admin.facebook-pages.edit', $page) }}"
                                       class="btn-secondary btn-sm"
                                       title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <button type="submit"
                                            form="form-toggle-{{ $page->id }}"
                                            class="btn-sm {{ $page->is_active ? 'btn-danger' : 'btn-success' }}"
                                            title="{{ $page->is_active ? 'Désactiver' : 'Activer' }}"
                                            onclick="return confirm('{{ $page->is_active ? 'Désactiver' : 'Activer' }} la page « {{ addslashes($page->page_name) }} » ?')">
                                        <i class="fas {{ $page->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
