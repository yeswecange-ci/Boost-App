@extends('layouts.auth')

@section('content')

<h2 style="font-size:1.375rem; font-weight:700; color:#0f172a; margin:0 0 0.25rem;">
    Créer un compte
</h2>
<p style="font-size:0.875rem; color:#64748b; margin:0 0 1.75rem;">
    Renseignez vos informations pour rejoindre l'équipe.
</p>

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div style="margin-bottom:1.125rem;">
        <label for="name" class="form-label">Nom complet</label>
        <div class="input-wrap">
            <input id="name"
                   type="text"
                   name="name"
                   value="{{ old('name') }}"
                   required
                   autocomplete="name"
                   autofocus
                   class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                   placeholder="Jean Dupont">
            <i class="fas fa-user input-icon"></i>
        </div>
        @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="margin-bottom:1.125rem;">
        <label for="email" class="form-label">Adresse e-mail</label>
        <div class="input-wrap">
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autocomplete="email"
                   class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                   placeholder="vous@exemple.com">
            <i class="fas fa-envelope input-icon"></i>
        </div>
        @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="margin-bottom:1.125rem;">
        <label for="password" class="form-label">Mot de passe</label>
        <div class="input-wrap" x-data="{ show: false }">
            <input id="password"
                   :type="show ? 'text' : 'password'"
                   name="password"
                   required
                   autocomplete="new-password"
                   class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                   placeholder="Minimum 8 caractères">
            <i class="fas fa-lock input-icon"></i>
            <button type="button" class="toggle-pw" @click="show = !show" tabindex="-1">
                <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
        </div>
        @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="margin-bottom:1.75rem;">
        <label for="password-confirm" class="form-label">Confirmer le mot de passe</label>
        <div class="input-wrap">
            <input id="password-confirm"
                   type="password"
                   name="password_confirmation"
                   required
                   autocomplete="new-password"
                   class="form-control"
                   placeholder="Répétez le mot de passe">
            <i class="fas fa-lock input-icon"></i>
        </div>
    </div>

    <button type="submit" class="btn-auth">
        <i class="fas fa-user-plus"></i>
        Créer mon compte
    </button>

</form>

@endsection

@section('card-footer')
<p style="margin:0; font-size:0.875rem; color:#64748b;">
    Déjà un compte ?
    <a href="{{ route('login') }}" style="color:var(--color-primary); font-weight:600; text-decoration:none;">
        Se connecter
    </a>
</p>
@endsection
