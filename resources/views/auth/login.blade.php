@extends('layouts.auth')

@section('content')

<h2 style="font-size:1.125rem; font-weight:600; color:#0f172a; margin:0 0 0.25rem;">
    Connexion
</h2>
<p style="font-size:0.875rem; color:#64748b; margin:0 0 1.5rem;">
    Entrez vos identifiants pour accéder à votre espace.
</p>

@if(session('status'))
<div class="alert alert-success" style="margin-bottom:1.25rem;">
    {{ session('status') }}
</div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <div style="margin-bottom:1rem;">
        <label for="email" class="form-label">Adresse e-mail</label>
        <div class="input-wrap">
            <input id="email"
                   type="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autocomplete="email"
                   autofocus
                   class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                   placeholder="vous@exemple.com">
            <i class="fas fa-envelope input-icon"></i>
        </div>
        @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="margin-bottom:1.25rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.375rem;">
            <label for="password" class="form-label" style="margin:0;">Mot de passe</label>
            @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}"
               style="font-size:0.8125rem; color:#4f46e5; text-decoration:none;">
                Mot de passe oublié ?
            </a>
            @endif
        </div>
        <div class="input-wrap" x-data="{ show: false }">
            <input id="password"
                   :type="show ? 'text' : 'password'"
                   name="password"
                   required
                   autocomplete="current-password"
                   class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                   placeholder="••••••••">
            <i class="fas fa-lock input-icon"></i>
            <button type="button" class="toggle-pw" @click="show = !show" tabindex="-1">
                <i :class="show ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
        </div>
        @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div style="display:flex; align-items:center; gap:0.5rem; margin-bottom:1.5rem;">
        <input type="checkbox"
               id="remember"
               name="remember"
               {{ old('remember') ? 'checked' : '' }}
               style="width:1rem; height:1rem; accent-color:#4f46e5; cursor:pointer;">
        <label for="remember" style="font-size:0.875rem; color:#374151; cursor:pointer; margin:0;">
            Se souvenir de moi
        </label>
    </div>

    <button type="submit" class="btn-auth">
        Se connecter
    </button>

</form>

@endsection

@section('card-footer')
@if (Route::has('register'))
<!-- <p style="margin:0; font-size:0.875rem; color:#64748b;">
    Pas encore de compte ?
    <a href="{{ route('register') }}" style="color:#4f46e5; font-weight:500; text-decoration:none;">
        Créer un compte
    </a>
</p> -->
@endif
@endsection
