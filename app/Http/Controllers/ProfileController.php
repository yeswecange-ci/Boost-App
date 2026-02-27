<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile.show', ['user' => auth()->user()]);
    }

    public function updateInfo(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user->update($validated);

        return back()->with('success_info', 'Informations mises à jour avec succès.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = auth()->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.'])
                ->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success_password', 'Mot de passe modifié avec succès.');
    }

    public function updateAvatar(Request $request)
    {
        $user = auth()->user();

        // Custom image upload
        if ($request->hasFile('avatar_file')) {
            $request->validate([
                'avatar_file' => ['required', 'image', 'max:2048', 'mimes:jpeg,png,jpg,gif,webp'],
            ]);

            // Delete old uploaded avatar
            if ($user->avatar && str_starts_with($user->avatar, 'avatars/')) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar_file')->store('avatars', 'public');
            $user->update(['avatar' => $path]);

            return back()->with('success_avatar', 'Photo de profil mise à jour.');
        }

        // Preset avatar color
        if ($request->filled('avatar_preset')) {
            $allowed = ['indigo', 'violet', 'rose', 'amber', 'emerald', 'sky', 'slate'];
            $preset = $request->input('avatar_preset');

            if (! in_array($preset, $allowed)) {
                return back()->withErrors(['avatar_preset' => 'Avatar invalide.']);
            }

            // Delete old uploaded file if switching to preset
            if ($user->avatar && str_starts_with($user->avatar, 'avatars/')) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->update(['avatar' => 'preset:' . $preset]);

            return back()->with('success_avatar', 'Avatar mis à jour.');
        }

        return back()->withErrors(['avatar' => 'Aucune image ou avatar fourni.']);
    }
}
