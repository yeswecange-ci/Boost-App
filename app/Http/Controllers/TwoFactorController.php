<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ─────────────────────────────────────────────────────────────────
    // VÉRIFICATION après login (si 2FA activé)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Affiche le formulaire de saisie du code OTP après login.
     */
    public function showVerify(Request $request)
    {
        // Déjà vérifié → rediriger vers l'accueil
        if ($request->session()->get('two_factor_verified')) {
            return redirect()->intended('/home');
        }

        return view('auth.two-factor-verify');
    }

    /**
     * Valide le code OTP saisi par l'utilisateur.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'one_time_password' => ['required', 'string', 'digits:6'],
        ]);

        $user   = auth()->user();
        $secret = $this->decryptSecret($user->two_factor_secret);

        $valid = $this->google2fa->verifyKey($secret, $request->one_time_password);

        if (! $valid) {
            return back()->withErrors([
                'one_time_password' => 'Code incorrect. Vérifiez votre application d\'authentification.',
            ]);
        }

        // Marque la 2FA comme vérifiée pour cette session
        $request->session()->put('two_factor_verified', true);

        return redirect()->intended('/home');
    }

    // ─────────────────────────────────────────────────────────────────
    // SETUP — activation depuis le profil
    // ─────────────────────────────────────────────────────────────────

    /**
     * Génère un secret, le stocke temporairement en session, et affiche le QR code.
     * NB : le secret n'est pas encore enregistré en DB — il le sera
     * uniquement après confirmation du premier code valide.
     */
    public function showSetup(Request $request)
    {
        $user = auth()->user();

        // Si déjà activé → afficher la page avec option de désactivation
        if ($user->two_factor_enabled) {
            return view('profile.two-factor', ['enabled' => true]);
        }

        // Générer un nouveau secret et le mettre en session (pas encore en DB)
        $secret = $this->google2fa->generateSecretKey(32);
        $request->session()->put('2fa_setup_secret', $secret);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        // Générer le QR code en SVG via bacon/bacon-qr-code
        $qrSvg = $this->generateQrSvg($qrCodeUrl);

        return view('profile.two-factor', [
            'enabled' => false,
            'secret'  => $secret,
            'qrSvg'   => $qrSvg,
        ]);
    }

    /**
     * Vérifie le code de confirmation et active la 2FA sur le compte.
     */
    public function enable(Request $request)
    {
        $request->validate([
            'one_time_password' => ['required', 'string', 'digits:6'],
        ]);

        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret) {
            return redirect()->route('2fa.setup')
                ->withErrors(['one_time_password' => 'Session expirée. Recommencez la configuration.']);
        }

        $valid = $this->google2fa->verifyKey($secret, $request->one_time_password);

        if (! $valid) {
            return back()->withErrors([
                'one_time_password' => 'Code incorrect. Scannez à nouveau le QR code et réessayez.',
            ]);
        }

        $user = auth()->user();
        $user->update([
            'two_factor_secret'  => encrypt($secret),
            'two_factor_enabled' => true,
        ]);

        // Marque la session comme vérifiée (l'utilisateur vient de prouver son accès)
        $request->session()->forget('2fa_setup_secret');
        $request->session()->put('two_factor_verified', true);

        return redirect()->route('profile.show')
            ->with('success_2fa', 'Double authentification activée avec succès ! Votre compte est maintenant protégé.');
    }

    /**
     * Désactive la 2FA après confirmation du code actuel.
     */
    public function disable(Request $request)
    {
        $request->validate([
            'one_time_password' => ['required', 'string', 'digits:6'],
        ]);

        $user   = auth()->user();
        $secret = $this->decryptSecret($user->two_factor_secret);

        $valid = $this->google2fa->verifyKey($secret, $request->one_time_password);

        if (! $valid) {
            return back()->withErrors([
                'one_time_password' => 'Code incorrect. La désactivation a été refusée.',
            ]);
        }

        $user->update([
            'two_factor_secret'  => null,
            'two_factor_enabled' => false,
        ]);

        $request->session()->forget('two_factor_verified');

        return redirect()->route('profile.show')
            ->with('success_2fa', 'Double authentification désactivée.');
    }

    // ─────────────────────────────────────────────────────────────────

    /**
     * Déchiffre le secret TOTP (stocké chiffré avec APP_KEY).
     */
    private function decryptSecret(?string $encrypted): string
    {
        if (! $encrypted) {
            return '';
        }
        try {
            return decrypt($encrypted);
        } catch (\Throwable) {
            // Legacy non chiffré (ne devrait pas arriver)
            return $encrypted;
        }
    }

    /**
     * Génère un QR code SVG à partir d'une URL en utilisant bacon/bacon-qr-code.
     */
    private function generateQrSvg(string $url): string
    {
        try {
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            );
            $writer = new \BaconQrCode\Writer($renderer);
            return $writer->writeString($url);
        } catch (\Throwable) {
            // Fallback : lien texte si la lib n'est pas disponible
            return '';
        }
    }
}
