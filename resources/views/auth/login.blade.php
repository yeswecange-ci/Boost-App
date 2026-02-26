@extends('layouts.auth')

@section('content')

<h2 style="font-size:1.625rem; font-weight:700; color:#0f172a; margin:0 0 0.375rem;">
    Connexion
</h2>
<p style="font-size:0.9rem; color:#64748b; margin:0 0 2rem;">
    Bienvenue ! Connectez-vous à votre compte.
</p>

@if(session('status'))
<div class="alert alert-success">
    {{ session('status') }}
</div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div style="margin-bottom: 1.25rem;">
        <label for="email" class="form-label">Adresse e-mail</label>
        <input id="email"
               type="email"
               name="email"
               value="{{ old('email') }}"
               required
               autocomplete="email"
               autofocus
               class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
               placeholder="vous@exemple.com">
        @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="margin-bottom: 1.5rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.375rem;">
            <label for="password" class="form-label" style="margin:0;">Mot de passe</label>
            @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}"
               style="font-size:0.8125rem; color:var(--color-primary); text-decoration:none;">
                Mot de passe oublié ?
            </a>
            @endif
        </div>
        <input id="password"
               type="password"
               name="password"
               required
               autocomplete="current-password"
               class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
               placeholder="••••••••">
        @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1.5rem;">
        <input type="checkbox"
               id="remember"
               name="remember"
               {{ old('remember') ? 'checked' : '' }}
               style="width:1rem; height:1rem; accent-color:var(--color-primary);">
        <label for="remember" style="font-size:0.875rem; color:#374151; cursor:pointer;">
            Se souvenir de moi
        </label>
    </div>

    <button type="submit" class="btn-primary" style="width:100%; justify-content:center; padding: 0.625rem 1rem; font-size:0.9375rem;">
        <i class="fas fa-arrow-right-to-bracket"></i>
        Se connecter
    </button>

    @if (Route::has('register'))
    <p style="text-align:center; margin-top:1.5rem; font-size:0.875rem; color:#64748b;">
        Pas encore de compte ?
        <a href="{{ route('register') }}" style="color:var(--color-primary); font-weight:500; text-decoration:none;">
            Créer un compte
        </a>
    </p>
    @endif

</form>

@endsection
