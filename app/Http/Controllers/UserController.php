<?php

namespace App\Http\Controllers;

use App\Models\FacebookPage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    const ROLES = ['admin', 'validator_n1', 'validator_n2', 'validator', 'operator'];

    public function index(Request $request)
    {
        $role  = $request->input('role');
        $query = User::with('roles')->latest();

        if ($role && in_array($role, self::ROLES)) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        $users = $query->with('facebookPages')->paginate(15)->withQueryString();

        return view('users.index', compact('users', 'role'));
    }

    public function create()
    {
        $roles = self::ROLES;
        $pages = FacebookPage::where('is_active', true)->orderBy('page_name')->get();
        return view('users.create', compact('roles', 'pages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8|confirmed',
            'phone'     => 'nullable|string|max:20',
            'role'      => 'required|in:' . implode(',', self::ROLES),
            'is_active' => 'boolean',
            'page_ids'  => 'nullable|array',
            'page_ids.*'=> 'integer|exists:facebook_pages,id',
        ]);

        $user = User::create([
            'name'                => $data['name'],
            'email'               => $data['email'],
            'password'            => Hash::make($data['password']),
            'phone'               => $data['phone'] ?? null,
            'is_active'           => $request->boolean('is_active'),
            'two_factor_required' => true, // 2FA obligatoire par défaut pour tout nouveau compte
        ]);

        $user->assignRole($data['role']);

        if ($data['role'] !== 'admin') {
            $user->facebookPages()->sync($request->input('page_ids', []));
        }

        return redirect()->route('users.index')
                         ->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        $roles = self::ROLES;
        $pages = FacebookPage::where('is_active', true)->orderBy('page_name')->get();
        $user->load('facebookPages');
        return view('users.edit', compact('user', 'roles', 'pages'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->id,
            'password'  => 'nullable|string|min:8|confirmed',
            'phone'     => 'nullable|string|max:20',
            'role'      => 'required|in:' . implode(',', self::ROLES),
            'is_active' => 'boolean',
            'page_ids'  => 'nullable|array',
            'page_ids.*'=> 'integer|exists:facebook_pages,id',
        ]);

        $user->update([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        if (!empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        $user->syncRoles([$data['role']]);

        if ($data['role'] !== 'admin') {
            $user->facebookPages()->sync($request->input('page_ids', []));
        }

        return redirect()->route('users.index')
                         ->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                             ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        // Empêche de supprimer le dernier administrateur (lockout)
        if ($user->hasRole('admin')) {
            $adminCount = User::role('admin')->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return redirect()->route('users.index')
                                 ->with('error', 'Impossible de supprimer le dernier administrateur actif.');
            }
        }

        $user->delete();

        return redirect()->route('users.index')
                         ->with('success', 'Utilisateur supprimé.');
    }

    /**
     * Déverrouille un compte bloqué après trop de tentatives échouées (admin uniquement).
     */
    public function unlock(User $user)
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_at'             => null,
        ]);

        return redirect()->route('users.index')
            ->with('success', "Compte de {$user->name} déverrouillé avec succès.");
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        $msg = $user->is_active ? 'Compte activé.' : 'Compte désactivé.';

        return redirect()->route('users.index')->with('success', $msg);
    }

    /**
     * Force la 2FA sur un compte spécifique (admin uniquement).
     * Au prochain login, l'utilisateur devra configurer son authenticator.
     */
    public function force2fa(User $user)
    {
        // Si la 2FA est déjà active, rien à faire
        if ($user->two_factor_enabled) {
            return redirect()->route('users.index')
                ->with('success', "{$user->name} a déjà la 2FA activée.");
        }

        $user->update(['two_factor_required' => true]);

        return redirect()->route('users.index')
            ->with('success', "2FA obligatoire activée pour {$user->name}. Il devra la configurer à sa prochaine connexion.");
    }

    /**
     * Force la 2FA sur tous les comptes qui ne l'ont pas encore configurée (admin uniquement).
     */
    public function force2faAll()
    {
        $count = User::where('two_factor_enabled', false)
            ->where('two_factor_required', false)
            ->update(['two_factor_required' => true]);

        return redirect()->route('users.index')
            ->with('success', "2FA rendue obligatoire pour {$count} compte(s). Ils devront la configurer à leur prochaine connexion.");
    }
}
