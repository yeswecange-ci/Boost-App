@extends('layouts.app')

@section('page-title', 'Modifier la page')
@section('page-subtitle', $page->page_name . ' · ' . $page->page_id)

@section('content')
<div style="max-width:640px;">
    <div class="card">
        <div class="card-header" style="display:flex; align-items:center; gap:0.75rem;">
            <div style="width:36px; height:36px; border-radius:8px; background:#1877f2; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="fab fa-facebook-f" style="color:#fff;"></i>
            </div>
            <div>
                <h2 style="font-size:1rem; font-weight:600; color:#0f172a; margin:0;">{{ $page->page_name }}</h2>
                <p style="font-size:0.8125rem; color:#64748b; margin:0;">ID Facebook : {{ $page->page_id }}</p>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.facebook-pages.update', $page) }}">
                @csrf
                @method('PUT')

                <div style="display:flex; flex-direction:column; gap:1rem;">

                    <div>
                        <label class="form-label">Nom de la page <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="page_name"
                               class="form-control @error('page_name') is-invalid @enderror"
                               value="{{ old('page_name', $page->page_name) }}" required>
                        @error('page_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label">Compte publicitaire (Act ID)</label>
                        <input type="text" name="ad_account_id"
                               class="form-control @error('ad_account_id') is-invalid @enderror"
                               value="{{ old('ad_account_id', $page->ad_account_id) }}"
                               placeholder="ex: act_1234567890">
                        <p style="font-size:0.75rem; color:#94a3b8; margin:0.375rem 0 0;">
                            Nécessaire pour créer des campagnes publicitaires Meta Ads.
                        </p>
                        @error('ad_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label">Compte Instagram (optionnel)</label>
                        <input type="text" name="instagram_account_id"
                               class="form-control"
                               value="{{ old('instagram_account_id', $page->instagram_account_id) }}"
                               placeholder="ID Instagram Business Account">
                    </div>

                    <div>
                        <label class="form-label">Page Access Token <span style="color:#ef4444;">*</span></label>
                        <textarea name="access_token" rows="3"
                                  class="form-control @error('access_token') is-invalid @enderror"
                                  placeholder="EAAxxxxx..."
                                  required>{{ old('access_token', $page->makeVisible('access_token')->access_token) }}</textarea>
                        <p style="font-size:0.75rem; color:#94a3b8; margin:0.375rem 0 0;">
                            Token de page Facebook avec les permissions <code>pages_read_engagement</code>, <code>ads_management</code>.
                        </p>
                        @error('access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                </div>

                <div class="card-footer" style="display:flex; gap:0.75rem; margin-top:1.5rem; padding:0;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save" style="margin-right:0.5rem;"></i>
                        Enregistrer les modifications
                    </button>
                    <a href="{{ route('admin.facebook-pages.index') }}" class="btn-secondary">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
