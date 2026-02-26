<?php

namespace App\Http\Controllers;

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

        $users = $query->paginate(15)->withQueryString();

        return view('users.index', compact('users', 'role'));
    }

    public function create()
    {
        $roles = self::ROLES;
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'phone'                 => 'nullable|string|max:20',
            'role'                  => 'required|in:' . implode(',', self::ROLES),
            'is_active'             => 'boolean',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'phone'     => $data['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->assignRole($data['role']);

        return redirect()->route('users.index')
                         ->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(User $user)
    {
        $roles = self::ROLES;
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email,' . $user->id,
            'password'              => 'nullable|string|min:8|confirmed',
            'phone'                 => 'nullable|string|max:20',
            'role'                  => 'required|in:' . implode(',', self::ROLES),
            'is_active'             => 'boolean',
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

        return redirect()->route('users.index')
                         ->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                             ->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $user->delete();

        return redirect()->route('users.index')
                         ->with('success', 'Utilisateur supprimé.');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        $msg = $user->is_active ? 'Compte activé.' : 'Compte désactivé.';

        return redirect()->route('users.index')->with('success', $msg);
    }
}
