@extends('layouts.app')

@section('page-title', 'Nouvel utilisateur')
@section('page-subtitle', 'Créer un compte et assigner un rôle')

@section('content')

<div style="max-width:640px; margin:0 auto;">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user-plus" style="color:var(--color-primary);"></i>
            Informations du compte
        </div>
        <div class="card-body">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf

                {{-- Nom --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="name">Nom complet <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="name" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}"
                           placeholder="Jean Dupont"
                           required>
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Email --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="email">Adresse email <span style="color:#ef4444;">*</span></label>
                    <input type="email" id="email" name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}"
                           placeholder="jean@exemple.com"
                           required>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Téléphone --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="phone">Téléphone <span style="color:#94a3b8; font-weight:400;">(optionnel)</span></label>
                    <input type="text" id="phone" name="phone"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}"
                           placeholder="+225 07 00 00 00 00">
                    @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Mot de passe --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="password">Mot de passe <span style="color:#ef4444;">*</span></label>
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Minimum 8 caractères"
                           required>
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Confirmation --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe <span style="color:#ef4444;">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control"
                           placeholder="Répétez le mot de passe"
                           required>
                </div>

                {{-- Rôle --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="role">Rôle <span style="color:#ef4444;">*</span></label>
                    <select id="role" name="role"
                            class="form-control @error('role') is-invalid @enderror"
                            required>
                        <option value="">— Choisir un rôle —</option>
                        <option value="admin"        {{ old('role') === 'admin'        ? 'selected' : '' }}>Admin — accès complet</option>
                        <option value="validator_n1" {{ old('role') === 'validator_n1' ? 'selected' : '' }}>Validateur N+1 — première validation</option>
                        <option value="validator_n2" {{ old('role') === 'validator_n2' ? 'selected' : '' }}>Validateur N+2 — validation finale</option>
                        <option value="validator"    {{ old('role') === 'validator'    ? 'selected' : '' }}>Validateur — validation N+1 et N+2</option>
                        <option value="operator"     {{ old('role') === 'operator'     ? 'selected' : '' }}>Opérateur — soumission de boosts</option>
                    </select>
                    @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Compte actif --}}
                <div style="margin-bottom:1.75rem;">
                    <label style="display:flex; align-items:center; gap:0.625rem; cursor:pointer;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               style="width:1rem; height:1rem; cursor:pointer;"
                               {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <span class="form-label" style="margin:0; cursor:pointer;">Compte actif</span>
                    </label>
                    <p style="font-size:0.8125rem; color:#64748b; margin:0.375rem 0 0 1.625rem;">
                        Un compte inactif ne peut pas se connecter.
                    </p>
                </div>

                {{-- Actions --}}
                <div style="display:flex; gap:0.75rem; justify-content:flex-end; border-top:1px solid var(--color-border); padding-top:1.25rem;">
                    <a href="{{ route('users.index') }}" class="btn-secondary">
                        Annuler
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-check"></i>
                        Créer l'utilisateur
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection
