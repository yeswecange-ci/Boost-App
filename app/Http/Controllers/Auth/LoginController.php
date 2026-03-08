<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Appelé par le trait AuthenticatesUsers juste après une authentification réussie.
     *
     * 1. Vérifie que le compte est actif.
     * 2. Si la 2FA est activée → redirige vers la vérification OTP
     *    (la session est créée mais le flag two_factor_verified n'est pas encore mis).
     */
    protected function authenticated(Request $request, $user): mixed
    {
        // Compte désactivé → logout immédiat
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Votre compte a été désactivé. Contactez un administrateur.']);
        }

        // 2FA activée → forcer la vérification OTP avant d'accéder à l'app
        if ($user->two_factor_enabled) {
            return redirect()->route('2fa.verify');
        }

        return redirect()->intended($this->redirectPath());
    }
}
