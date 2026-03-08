@extends('layouts.app')

@section('page-title', 'Double authentification (2FA)')
@section('page-subtitle', 'Protégez votre compte avec un code à usage unique')

@section('content')
<div style="max-width:600px; margin:0 auto;">

    @if(session('success_2fa'))
    <div class="alert alert-success" style="margin-bottom:1.5rem;">
        <i class="fas fa-shield-halved"></i> {{ session('success_2fa') }}
    </div>
    @endif

    @if($enabled)
    {{-- ── 2FA déjà activée ── --}}
    <div class="card">
        <div class="card-header" style="display:flex; align-items:center; gap:0.75rem;">
            <div style="
                width:40px; height:40px; border-radius:0.5rem;
                background:#dcfce7; display:flex; align-items:center; justify-content:center;
                flex-shrink:0;
            ">
                <i class="fas fa-shield-halved" style="color:#16a34a; font-size:1.1rem;"></i>
            </div>
            <div>
                <h2 style="font-size:1rem; font-weight:600; margin:0; color:var(--color-heading);">
                    Double authentification activée
                </h2>
                <p style="font-size:0.8125rem; color:#16a34a; margin:0; font-weight:500;">
                    <i class="fas fa-check-circle"></i> Votre compte est protégé
                </p>
            </div>
        </div>
        <div class="card-body">
            <p style="font-size:0.875rem; color:var(--color-muted); margin:0 0 1.5rem; line-height:1.6;">
                La vérification en deux étapes est active. À chaque connexion, vous devrez entrer
                le code affiché dans votre application d'authentification (Google Authenticator, Authy…).
            </p>

            <div style="
                background:#fef2f2; border:1px solid #fecaca; border-radius:0.75rem;
                padding:1rem 1.25rem; margin-bottom:1.5rem;
            ">
                <p style="font-size:0.875rem; font-weight:600; color:#dc2626; margin:0 0 0.375rem;">
                    <i class="fas fa-triangle-exclamation"></i> Désactiver la 2FA
                </p>
                <p style="font-size:0.8125rem; color:#991b1b; margin:0; line-height:1.5;">
                    Entrez votre code actuel pour confirmer la désactivation.
                    Cette action réduira la sécurité de votre compte.
                </p>
            </div>

            <form method="POST" action="{{ route('2fa.disable') }}" x-data autocomplete="off">
                @csrf

                @if($errors->any())
                <div class="alert alert-danger" style="margin-bottom:1rem;">
                    <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
                </div>
                @endif

                <div style="margin-bottom:1rem;">
                    <label class="form-label">Code de confirmation (application d'auth)</label>
                    <input type="text"
                           name="one_time_password"
                           inputmode="numeric"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           autocomplete="one-time-code"
                           class="form-control @error('one_time_password') is-invalid @enderror"
                           placeholder="000000"
                           style="letter-spacing:0.25rem; font-size:1.25rem; text-align:center; max-width:180px;">
                </div>

                <button type="submit" class="btn-danger btn-sm">
                    <i class="fas fa-shield-slash"></i> Désactiver la double authentification
                </button>
            </form>
        </div>
    </div>

    @else
    {{-- ── Setup : QR code + activation ── --}}
    <div class="card">
        <div class="card-header">
            <h2 style="font-size:1rem; font-weight:600; margin:0;">
                Configurer la double authentification
            </h2>
        </div>
        <div class="card-body">

            @if($errors->any())
            <div class="alert alert-danger" style="margin-bottom:1.25rem;">
                <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
            </div>
            @endif

            {{-- Étape 1 --}}
            <div style="display:flex; gap:0.75rem; margin-bottom:1.25rem; align-items:flex-start;">
                <div style="
                    width:28px; height:28px; border-radius:50%;
                    background:var(--color-primary); color:#fff;
                    display:flex; align-items:center; justify-content:center;
                    font-size:0.8125rem; font-weight:700; flex-shrink:0; margin-top:1px;
                ">1</div>
                <div>
                    <p style="font-size:0.875rem; font-weight:600; color:var(--color-heading); margin:0 0 0.25rem;">
                        Téléchargez une application d'authentification
                    </p>
                    <p style="font-size:0.8125rem; color:var(--color-muted); margin:0; line-height:1.5;">
                        <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2"
                           target="_blank" rel="noopener noreferrer"
                           style="color:var(--color-primary);">Google Authenticator</a>,
                        <a href="https://authy.com/download/" target="_blank" rel="noopener noreferrer"
                           style="color:var(--color-primary);">Authy</a>
                        ou toute application TOTP compatible.
                    </p>
                </div>
            </div>

            {{-- Étape 2 : QR code --}}
            <div style="display:flex; gap:0.75rem; margin-bottom:1.25rem; align-items:flex-start;">
                <div style="
                    width:28px; height:28px; border-radius:50%;
                    background:var(--color-primary); color:#fff;
                    display:flex; align-items:center; justify-content:center;
                    font-size:0.8125rem; font-weight:700; flex-shrink:0; margin-top:1px;
                ">2</div>
                <div style="flex:1;">
                    <p style="font-size:0.875rem; font-weight:600; color:var(--color-heading); margin:0 0 0.75rem;">
                        Scannez ce QR code avec votre application
                    </p>

                    @if($qrSvg)
                    <div style="
                        display:inline-block; padding:0.75rem;
                        background:#fff; border:2px solid var(--color-border);
                        border-radius:0.75rem; margin-bottom:0.75rem;
                    ">
                        {!! $qrSvg !!}
                    </div>
                    @endif

                    {{-- Clé manuelle en fallback --}}
                    <details style="margin-top:0.5rem;">
                        <summary style="font-size:0.8125rem; color:var(--color-muted); cursor:pointer;">
                            Saisir la clé manuellement
                        </summary>
                        <div style="
                            margin-top:0.5rem; padding:0.625rem 0.875rem;
                            background:#f8fafc; border:1px solid var(--color-border);
                            border-radius:0.5rem; display:flex; align-items:center; gap:0.75rem;
                        ">
                            <code style="font-size:0.875rem; letter-spacing:0.1rem; color:var(--color-heading); word-break:break-all;">
                                {{ wordwrap($secret, 4, ' ', true) }}
                            </code>
                            <button type="button"
                                    onclick="navigator.clipboard.writeText('{{ $secret }}').then(() => this.innerHTML='<i class=\'fas fa-check\'></i>')"
                                    style="
                                        background:none; border:none; cursor:pointer;
                                        color:var(--color-primary); flex-shrink:0;
                                    "
                                    title="Copier">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </details>
                </div>
            </div>

            {{-- Étape 3 : Confirmation --}}
            <div style="display:flex; gap:0.75rem; align-items:flex-start;">
                <div style="
                    width:28px; height:28px; border-radius:50%;
                    background:var(--color-primary); color:#fff;
                    display:flex; align-items:center; justify-content:center;
                    font-size:0.8125rem; font-weight:700; flex-shrink:0; margin-top:1px;
                ">3</div>
                <div style="flex:1;">
                    <p style="font-size:0.875rem; font-weight:600; color:var(--color-heading); margin:0 0 0.75rem;">
                        Entrez le code affiché pour confirmer l'activation
                    </p>

                    <form method="POST" action="{{ route('2fa.enable') }}" autocomplete="off">
                        @csrf
                        <div style="display:flex; gap:0.75rem; align-items:flex-end; flex-wrap:wrap;">
                            <div>
                                <label class="form-label">Code de vérification</label>
                                <input type="text"
                                       name="one_time_password"
                                       inputmode="numeric"
                                       maxlength="6"
                                       pattern="[0-9]{6}"
                                       autocomplete="one-time-code"
                                       class="form-control @error('one_time_password') is-invalid @enderror"
                                       placeholder="000000"
                                       style="letter-spacing:0.25rem; font-size:1.25rem; text-align:center; max-width:180px;"
                                       autofocus>
                                @error('one_time_password')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-shield-halved"></i> Activer la 2FA
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    @endif

    <div style="margin-top:1rem;">
        <a href="{{ route('profile.show') }}"
           style="font-size:0.875rem; color:var(--color-muted); text-decoration:none;">
            <i class="fas fa-arrow-left"></i> Retour au profil
        </a>
    </div>

</div>
@endsection
