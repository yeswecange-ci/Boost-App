@extends('layouts.auth')

@section('content')

<h2 style="font-size:1.125rem; font-weight:600; color:#0f172a; margin:0 0 0.25rem;">
    Vérification en deux étapes
</h2>
<p style="font-size:0.875rem; color:#64748b; margin:0 0 1.5rem; line-height:1.5;">
    Ouvrez votre application d'authentification (Google Authenticator, Authy…)
    et entrez le code à 6 chiffres affiché.
</p>

@if ($errors->any())
<div class="alert alert-danger" style="margin-bottom:1.25rem;">
    <i class="fas fa-exclamation-circle"></i>
    {{ $errors->first() }}
</div>
@endif

<form id="otp-form" method="POST" action="{{ route('2fa.verify.post') }}" x-data autocomplete="off">
    @csrf

    {{-- OTP input avec 6 cases séparées --}}
    <div style="margin-bottom:1.5rem;">
        <label class="form-label" style="display:block; margin-bottom:0.75rem;">
            Code d'authentification
        </label>

        {{-- Input caché qui reçoit la valeur concaténée --}}
        <input type="hidden" name="one_time_password" id="otp-value">

        {{-- 6 cases visuelles --}}
        <div id="otp-boxes" style="display:flex; gap:0.5rem; justify-content:center;">
            @for ($i = 0; $i < 6; $i++)
            <input type="text"
                   maxlength="1"
                   inputmode="numeric"
                   pattern="[0-9]"
                   class="otp-digit"
                   style="
                       width:3rem; height:3.5rem;
                       text-align:center; font-size:1.5rem; font-weight:700;
                       border:2px solid var(--color-border); border-radius:0.5rem;
                       color:var(--color-heading); background:#fff;
                       outline:none; transition:border-color .15s;
                   "
                   onfocus="this.style.borderColor='var(--color-primary)'"
                   onblur="this.style.borderColor='var(--color-border)'">
            @endfor
        </div>
    </div>

    <button type="submit" class="btn-auth" id="btn-verify" disabled
            style="opacity:0.5; cursor:not-allowed;">
        <i class="fas fa-shield-halved"></i> Vérifier
    </button>

</form>

<div style="margin-top:1.5rem; text-align:center;">
    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
        @csrf
        <button type="submit" style="
            background:none; border:none; cursor:pointer;
            font-size:0.875rem; color:var(--color-muted);
            text-decoration:underline;
        ">
            Se déconnecter et utiliser un autre compte
        </button>
    </form>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const digits = Array.from(document.querySelectorAll('.otp-digit'));
    const hidden = document.getElementById('otp-value');
    const form   = document.getElementById('otp-form');

    function getCode() {
        return digits.map(d => d.value).join('');
    }

    function submit() {
        const code = getCode();
        hidden.value = code;
        form.submit();
    }

    function check() {
        const code = getCode();
        hidden.value = code;
        // Soumission automatique dès que les 6 chiffres sont saisis
        if (/^\d{6}$/.test(code)) {
            submit();
        }
    }

    digits.forEach((input, idx) => {

        // ── Bloquer les touches non numériques avant la saisie ──────
        input.addEventListener('keydown', function (e) {
            // Touches de navigation autorisées
            if (['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Enter'].includes(e.key)) {
                if (e.key === 'Enter') { e.preventDefault(); submit(); return; }
                if (e.key === 'ArrowLeft'  && idx > 0)              { e.preventDefault(); digits[idx - 1].focus(); return; }
                if (e.key === 'ArrowRight' && idx < digits.length - 1) { e.preventDefault(); digits[idx + 1].focus(); return; }
                if (e.key === 'Backspace') {
                    e.preventDefault();
                    if (this.value) {
                        this.value = '';
                    } else if (idx > 0) {
                        digits[idx - 1].value = '';
                        digits[idx - 1].focus();
                    }
                    check();
                    return;
                }
                return;
            }
            // Bloquer tout ce qui n'est pas un chiffre
            if (!/^\d$/.test(e.key)) {
                e.preventDefault();
            }
        });

        // ── Saisie : avancer automatiquement à la case suivante ─────
        input.addEventListener('input', function () {
            // Ne conserver que le dernier chiffre saisi (écrase la valeur existante)
            const val = this.value.replace(/\D/g, '');
            this.value = val ? val.slice(-1) : '';

            if (this.value && idx < digits.length - 1) {
                digits[idx + 1].focus();
            }
            check();
        });

        // ── Coller un code copié (6 chiffres d'un coup) ────────────
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData)
                .getData('text').replace(/\D/g, '').slice(0, 6);
            pasted.split('').forEach((char, i) => {
                if (digits[i]) digits[i].value = char;
            });
            // Focus sur la dernière case remplie ou la suivante vide
            const lastFilled = Math.min(pasted.length, digits.length) - 1;
            digits[lastFilled]?.focus();
            check();
        });

        // ── Clic sur une case déjà remplie : sélectionner le contenu ─
        input.addEventListener('focus', function () {
            this.select();
        });
    });

    // Focus automatique sur le premier champ à l'ouverture de la page
    digits[0]?.focus();
})();
</script>
@endpush
