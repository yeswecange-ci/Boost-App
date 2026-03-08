<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    const MAX_ATTEMPTS = 5;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Override du login pour vérifier le verrouillage AVANT la tentative.
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // Vérification du verrouillage admin
        $user = User::where($this->username(), $request->input($this->username()))->first();

        if ($user && $user->isLocked()) {
            throw ValidationException::withMessages([
                $this->username() => 'Votre compte a été verrouillé après trop de tentatives échouées. Contactez un administrateur pour le débloquer.',
            ]);
        }

        // Throttle Laravel (rate limiting par IP)
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Appelé après une authentification réussie.
     */
    protected function authenticated(Request $request, $user): mixed
    {
        // Réinitialise le compteur d'échecs
        $user->update(['failed_login_attempts' => 0]);

        // Compte désactivé → logout immédiat
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Votre compte a été désactivé. Contactez un administrateur.']);
        }

        // 2FA activée → forcer la vérification OTP
        if ($user->two_factor_enabled) {
            return redirect()->route('2fa.verify');
        }

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Appelé après chaque tentative échouée.
     * Incrémente le compteur et verrouille le compte après MAX_ATTEMPTS.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        $user = User::where($this->username(), $request->input($this->username()))->first();

        if ($user) {
            $attempts = $user->failed_login_attempts + 1;

            if ($attempts >= self::MAX_ATTEMPTS) {
                $user->update([
                    'failed_login_attempts' => $attempts,
                    'locked_at'             => now(),
                ]);

                throw ValidationException::withMessages([
                    $this->username() => "Compte verrouillé après {$attempts} tentatives incorrectes. Contactez un administrateur pour le débloquer.",
                ]);
            }

            $user->update(['failed_login_attempts' => $attempts]);
            $remaining = self::MAX_ATTEMPTS - $attempts;

            throw ValidationException::withMessages([
                $this->username() => "Mot de passe incorrect. Il vous reste {$remaining} tentative(s) avant le verrouillage du compte.",
            ]);
        }

        // Email inconnu : message générique (ne pas confirmer l'existence du compte)
        throw ValidationException::withMessages([
            $this->username() => __('auth.failed'),
        ]);
    }
}
