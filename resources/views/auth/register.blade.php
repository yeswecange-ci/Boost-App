@extends('layouts.auth')

@section('content')

<h2 style="font-size:1.625rem; font-weight:700; color:#0f172a; margin:0 0 0.375rem;">
    Créer un compte
</h2>
<p style="font-size:0.9rem; color:#64748b; margin:0 0 2rem;">
    Rejoignez Boost Manager dès aujourd'hui.
</p>

<form method="POST" action="{{ route('register') }}">
    @csrf

    <div style="margin-bottom: 1.25rem;">
        <label for="name" class="form-label">Nom complet</label>
        <input id="name"
               type="text"
               name="name"
               value="{{ old('name') }}"
               required
               autocomplete="name"
               autofocus
               class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
               placeholder="Jean Dupont">
        @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="margin-bottom: 1.25rem;">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input id="email"
               type="email"
               name="email"
               value="{{ old('email') }}"
               required
               autocomplete="email"
               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
               placeholder="vous@exemple.com">
        @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="margin-bottom: 1.25rem;">
        <label for="password" class="form-label">Mot de passe</label>
        <input id="password"
               type="password"
               name="password"
               required
               autocomplete="new-password"
               class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
               placeholder="Minimum 8 caractères">
        @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="margin-bottom: 1.75rem;">
        <label for="password-confirm" class="form-label">Confirmer le mot de passe</label>
        <input id="password-confirm"
               type="password"
               name="password_confirmation"
               required
               autocomplete="new-password"
               class="form-control"
               placeholder="Répétez le mot de passe">
    </div>

    <button type="submit" class="btn-primary" style="width:100%; justify-content:center; padding: 0.625rem 1rem; font-size:0.9375rem;">
        <i class="fas fa-user-plus"></i>
        Créer mon compte
    </button>

    <p style="text-align:center; margin-top:1.5rem; font-size:0.875rem; color:#64748b;">
        Déjà un compte ?
        <a href="{{ route('login') }}" style="color:var(--color-primary); font-weight:500; text-decoration:none;">
            Se connecter
        </a>
    </p>

</form>

@endsection
