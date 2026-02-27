@extends('layouts.app')

@section('page-title', 'Mon profil')
@section('page-subtitle', 'Gérez vos informations personnelles et vos préférences')

@php
    $user = auth()->user();

    $presetGradients = [
        'indigo'  => 'linear-gradient(135deg, #4f46e5, #7c3aed)',
        'violet'  => 'linear-gradient(135deg, #7c3aed, #a855f7)',
        'rose'    => 'linear-gradient(135deg, #e11d48, #f43f5e)',
        'amber'   => 'linear-gradient(135deg, #d97706, #f59e0b)',
        'emerald' => 'linear-gradient(135deg, #059669, #10b981)',
        'sky'     => 'linear-gradient(135deg, #0284c7, #38bdf8)',
        'slate'   => 'linear-gradient(135deg, #475569, #64748b)',
    ];

    $avatarIsImage   = $user->avatar && str_starts_with($user->avatar, 'avatars/');
    $avatarPresetKey = ($user->avatar && str_starts_with($user->avatar, 'preset:'))
                       ? str_replace('preset:', '', $user->avatar) : null;
    $currentGradient = $presetGradients[$avatarPresetKey ?? 'indigo'];
@endphp

<div style="max-width: 860px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

    {{-- ── Avatar card ── --}}
    <div class="card">
        <div class="card-header">
            <h2 style="font-size:1rem; font-weight:600; margin:0;">Photo de profil</h2>
        </div>
        <div class="card-body">

            @if(session('success_avatar'))
                <div class="alert alert-success" style="margin-bottom:1.25rem;">
                    <i class="fas fa-check-circle"></i> {{ session('success_avatar') }}
                </div>
            @endif

            <div style="display:flex; flex-wrap:wrap; gap:2rem; align-items:flex-start;">

                {{-- Current avatar preview --}}
                <div style="display:flex; flex-direction:column; align-items:center; gap:0.75rem;">
                    <div id="avatar-preview" style="
                        width:96px; height:96px; border-radius:50%; overflow:hidden;
                        display:flex; align-items:center; justify-content:center;
                        background: {{ $currentGradient }};
                        font-size:2.25rem; font-weight:700; color:#fff;
                        border: 3px solid var(--color-border);
                        flex-shrink:0;
                    ">
                        @if($avatarIsImage)
                            <img src="{{ asset('storage/' . $user->avatar) }}" alt="Avatar"
                                 style="width:100%; height:100%; object-fit:cover;" id="avatar-img">
                        @else
                            <span id="avatar-initials">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                        @endif
                    </div>
                    <span style="font-size:0.75rem; color:var(--color-muted);">Aperçu</span>
                </div>

                <div style="flex:1; min-width:240px;">

                    {{-- Preset color avatars --}}
                    <p style="font-size:0.875rem; font-weight:500; color:var(--color-heading); margin:0 0 0.75rem;">
                        Choisir un avatar coloré
                    </p>
                    <form method="POST" action="{{ route('profile.update-avatar') }}" id="form-preset">
                        @csrf
                        <div style="display:flex; gap:0.625rem; flex-wrap:wrap; margin-bottom:1.25rem;">
                            @foreach($presetGradients as $name => $gradient)
                                <button type="submit" name="avatar_preset" value="{{ $name }}"
                                    title="{{ ucfirst($name) }}"
                                    style="
                                        width:44px; height:44px; border-radius:50%; border:3px solid transparent;
                                        background: {{ $gradient }};
                                        cursor:pointer; display:flex; align-items:center; justify-content:center;
                                        font-size:1rem; font-weight:700; color:#fff;
                                        transition: transform .15s, border-color .15s;
                                        {{ ($avatarPresetKey === $name) ? 'border-color: var(--color-primary); transform:scale(1.15);' : '' }}
                                    "
                                    onmouseover="this.style.transform='scale(1.12)'"
                                    onmouseout="this.style.transform='{{ ($avatarPresetKey === $name) ? 'scale(1.15)' : 'scale(1)' }}'">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </button>
                            @endforeach
                        </div>
                    </form>

                    {{-- Custom image upload --}}
                    <p style="font-size:0.875rem; font-weight:500; color:var(--color-heading); margin:0 0 0.625rem;">
                        Ou télécharger une image personnelle
                    </p>
                    <form method="POST" action="{{ route('profile.update-avatar') }}"
                          enctype="multipart/form-data" id="form-upload">
                        @csrf
                        @error('avatar_file')
                            <div class="alert alert-danger" style="margin-bottom:0.75rem; padding:0.5rem 0.75rem; font-size:0.8125rem;">
                                {{ $message }}
                            </div>
                        @enderror
                        <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                            <label for="avatar_file" style="
                                display:inline-flex; align-items:center; gap:0.5rem;
                                padding:0.5rem 1rem; border-radius:0.5rem;
                                border:2px dashed var(--color-border);
                                cursor:pointer; font-size:0.875rem; color:var(--color-muted);
                                background:#fff; transition:border-color .15s;
                            " onmouseover="this.style.borderColor='var(--color-primary)'"
                               onmouseout="this.style.borderColor='var(--color-border)'">
                                <i class="fas fa-image" style="color:var(--color-primary);"></i>
                                Choisir un fichier
                            </label>
                            <input type="file" name="avatar_file" id="avatar_file"
                                   accept="image/*" style="display:none;"
                                   onchange="previewImage(this)">
                            <span id="file-name" style="font-size:0.8125rem; color:var(--color-muted);">
                                Aucun fichier choisi
                            </span>
                            <button type="submit" class="btn-primary btn-sm" id="btn-upload" style="display:none;">
                                <i class="fas fa-upload"></i> Enregistrer
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    {{-- ── Informations personnelles ── --}}
    <div class="card">
        <div class="card-header">
            <h2 style="font-size:1rem; font-weight:600; margin:0;">Informations personnelles</h2>
        </div>
        <div class="card-body">

            @if(session('success_info'))
                <div class="alert alert-success" style="margin-bottom:1.25rem;">
                    <i class="fas fa-check-circle"></i> {{ session('success_info') }}
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update-info') }}">
                @csrf
                @method('PUT')

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                    <div style="grid-column: 1 / -1;">
                        <label class="form-label" for="name">Nom complet</label>
                        <input type="text" id="name" name="name"
                               value="{{ old('name', $user->name) }}"
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="Votre nom">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label" for="email">Adresse e-mail</label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email', $user->email) }}"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="vous@exemple.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label class="form-label" for="phone">Téléphone <span style="color:var(--color-muted); font-weight:400;">(optionnel)</span></label>
                        <input type="text" id="phone" name="phone"
                               value="{{ old('phone', $user->phone) }}"
                               class="form-control @error('phone') is-invalid @enderror"
                               placeholder="+225 07 XX XX XX XX">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div style="margin-top:1.25rem; display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Mot de passe ── --}}
    <div class="card">
        <div class="card-header">
            <h2 style="font-size:1rem; font-weight:600; margin:0;">Changer le mot de passe</h2>
        </div>
        <div class="card-body">

            @if(session('success_password'))
                <div class="alert alert-success" style="margin-bottom:1.25rem;">
                    <i class="fas fa-check-circle"></i> {{ session('success_password') }}
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update-password') }}"
                  x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                @csrf
                @method('PUT')

                <div style="display:flex; flex-direction:column; gap:1rem;">

                    {{-- Current password --}}
                    <div>
                        <label class="form-label" for="current_password">Mot de passe actuel</label>
                        <div style="position:relative;">
                            <input :type="showCurrent ? 'text' : 'password'"
                                   id="current_password" name="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   placeholder="••••••••"
                                   style="padding-right:2.75rem;">
                            <button type="button" @click="showCurrent = !showCurrent"
                                    style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%);
                                           background:none; border:none; cursor:pointer; color:var(--color-muted); padding:0;">
                                <i :class="showCurrent ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- New password --}}
                    <div>
                        <label class="form-label" for="password">Nouveau mot de passe</label>
                        <div style="position:relative;">
                            <input :type="showNew ? 'text' : 'password'"
                                   id="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="8 caractères minimum"
                                   style="padding-right:2.75rem;">
                            <button type="button" @click="showNew = !showNew"
                                    style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%);
                                           background:none; border:none; cursor:pointer; color:var(--color-muted); padding:0;">
                                <i :class="showNew ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Confirm password --}}
                    <div>
                        <label class="form-label" for="password_confirmation">Confirmer le nouveau mot de passe</label>
                        <div style="position:relative;">
                            <input :type="showConfirm ? 'text' : 'password'"
                                   id="password_confirmation" name="password_confirmation"
                                   class="form-control"
                                   placeholder="••••••••"
                                   style="padding-right:2.75rem;">
                            <button type="button" @click="showConfirm = !showConfirm"
                                    style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%);
                                           background:none; border:none; cursor:pointer; color:var(--color-muted); padding:0;">
                                <i :class="showConfirm ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                    </div>

                </div>

                <div style="margin-top:1.25rem; display:flex; justify-content:flex-end;">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-lock"></i> Mettre à jour le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Infos compte (lecture seule) ── --}}
    <div class="card">
        <div class="card-header">
            <h2 style="font-size:1rem; font-weight:600; margin:0;">Informations du compte</h2>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:1rem;">
                <div>
                    <p style="font-size:0.75rem; font-weight:500; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin:0 0 0.25rem;">Rôle</p>
                    <p style="font-size:0.9375rem; color:var(--color-heading); margin:0; font-weight:500;">
                        {{ $user->roles->first()?->name ?? 'Utilisateur' }}
                    </p>
                </div>
                <div>
                    <p style="font-size:0.75rem; font-weight:500; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin:0 0 0.25rem;">Membre depuis</p>
                    <p style="font-size:0.9375rem; color:var(--color-heading); margin:0; font-weight:500;">
                        {{ $user->created_at->format('d/m/Y') }}
                    </p>
                </div>
                <div>
                    <p style="font-size:0.75rem; font-weight:500; text-transform:uppercase; letter-spacing:.05em; color:var(--color-muted); margin:0 0 0.25rem;">Statut</p>
                    <p style="margin:0;">
                        @if($user->is_active)
                            <span class="badge-status-active">Actif</span>
                        @else
                            <span class="badge-status-paused">Inactif</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function previewImage(input) {
    const fileName = document.getElementById('file-name');
    const btnUpload = document.getElementById('btn-upload');
    const preview = document.getElementById('avatar-preview');

    if (input.files && input.files[0]) {
        fileName.textContent = input.files[0].name;
        btnUpload.style.display = 'inline-flex';

        const reader = new FileReader();
        reader.onload = function(e) {
            // Replace preview content with actual image
            preview.style.background = 'none';
            preview.innerHTML = `<img src="${e.target.result}" alt="Aperçu"
                style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        fileName.textContent = 'Aucun fichier choisi';
        btnUpload.style.display = 'none';
    }
}
</script>
@endpush
