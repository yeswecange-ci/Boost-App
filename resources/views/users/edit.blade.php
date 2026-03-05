@extends('layouts.app')

@section('page-title', 'Modifier l\'utilisateur')
@section('page-subtitle', '{{ $user->name }}')

@section('content')

<div style="max-width:640px; margin:0 auto;" x-data="{ role: '{{ old('role', $user->roles->first()?->name) }}' }">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user-pen" style="color:var(--color-primary);"></i>
            Modifier le compte
        </div>
        <div class="card-body">
            <form action="{{ route('users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Nom --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="name">Nom complet <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="name" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name) }}"
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
                           value="{{ old('email', $user->email) }}"
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
                           value="{{ old('phone', $user->phone) }}"
                           placeholder="+225 07 00 00 00 00">
                    @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Mot de passe --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="password">
                        Nouveau mot de passe
                        <span style="color:#94a3b8; font-weight:400;">(laisser vide pour ne pas changer)</span>
                    </label>
                    <input type="password" id="password" name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Minimum 8 caractères">
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Confirmation --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="password_confirmation">Confirmer le mot de passe</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-control"
                           placeholder="Répétez le nouveau mot de passe">
                </div>

                {{-- Rôle --}}
                <div style="margin-bottom:1.25rem;">
                    <label class="form-label" for="role">Rôle <span style="color:#ef4444;">*</span></label>
                    <select id="role" name="role"
                            x-model="role"
                            class="form-control @error('role') is-invalid @enderror"
                            required>
                        @php $currentRole = old('role', $user->roles->first()?->name); @endphp
                        <option value="">— Choisir un rôle —</option>
                        <option value="admin"        {{ $currentRole === 'admin'        ? 'selected' : '' }}>Admin — accès complet</option>
                        <option value="validator_n1" {{ $currentRole === 'validator_n1' ? 'selected' : '' }}>Validateur N+1 — première validation</option>
                        <option value="validator_n2" {{ $currentRole === 'validator_n2' ? 'selected' : '' }}>Validateur N+2 — validation finale</option>
                        <option value="validator"    {{ $currentRole === 'validator'    ? 'selected' : '' }}>Validateur — validation N+1 et N+2</option>
                        <option value="operator"     {{ $currentRole === 'operator'     ? 'selected' : '' }}>Opérateur — soumission de boosts</option>
                    </select>
                    @error('role')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Pages Facebook (masqué pour admin) --}}
                @if($pages->isNotEmpty())
                @php $assignedPageIds = old('page_ids') ? (array) old('page_ids') : $user->facebookPages->pluck('id')->toArray(); @endphp
                <div x-show="role !== 'admin'" style="margin-bottom:1.25rem;">
                    <label class="form-label">
                        Pages Facebook assignées
                        <span style="color:#94a3b8; font-weight:400;">(optionnel)</span>
                    </label>
                    <p style="font-size:0.8125rem; color:#64748b; margin:0 0 0.625rem;">
                        Cochez les pages que cet utilisateur pourra voir et gérer.
                    </p>
                    <div style="border:1px solid var(--color-border); border-radius:0.5rem; max-height:200px; overflow-y:auto; padding:0.25rem 0;">
                        @foreach($pages as $page)
                        <label style="display:flex; align-items:center; gap:0.625rem; padding:0.5rem 0.875rem; cursor:pointer; transition:background .1s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                            <input type="checkbox" name="page_ids[]" value="{{ $page->id }}"
                                   {{ in_array($page->id, $assignedPageIds) ? 'checked' : '' }}
                                   style="width:15px; height:15px; accent-color:var(--color-primary); cursor:pointer; flex-shrink:0;">
                            <i class="fab fa-facebook-square" style="color:#1877f2; font-size:1rem; flex-shrink:0;"></i>
                            <span style="font-size:0.875rem; color:#0f172a;">{{ $page->page_name }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('page_ids')
                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                    @enderror
                </div>
                @endif

                {{-- Compte actif --}}
                <div style="margin-bottom:1.75rem;">
                    <label style="display:flex; align-items:center; gap:0.625rem; cursor:pointer;">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               style="width:1rem; height:1rem; cursor:pointer;"
                               {{ old('is_active', $user->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
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
                        Enregistrer
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

@endsection
