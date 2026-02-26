@extends('layouts.app')

@section('page-title', 'Utilisateurs')
@section('page-subtitle', 'Gestion des comptes et des rôles')

@section('content')

@php
$roleLabels = [
    'admin'        => ['label' => 'Admin',          'color' => '#4f46e5', 'bg' => '#eef2ff'],
    'validator_n1' => ['label' => 'Validateur N+1', 'color' => '#0891b2', 'bg' => '#ecfeff'],
    'validator_n2' => ['label' => 'Validateur N+2', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
    'validator'    => ['label' => 'Validateur',     'color' => '#0d9488', 'bg' => '#f0fdfa'],
    'operator'     => ['label' => 'Opérateur',      'color' => '#ea580c', 'bg' => '#fff7ed'],
];
$tabs = [
    ''             => 'Tous',
    'admin'        => 'Admin',
    'validator_n1' => 'Validateur N+1',
    'validator_n2' => 'Validateur N+2',
    'validator'    => 'Validateur',
    'operator'     => 'Opérateur',
];
@endphp

{{-- Header row --}}
<div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
    <div class="tab-list">
        @foreach($tabs as $val => $label)
        <a href="{{ route('users.index', $val ? ['role' => $val] : []) }}"
           class="tab-item {{ $role === $val ? 'active' : '' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <div style="display:flex; align-items:center; gap:1rem;">
        <span style="font-size:0.875rem; color:#94a3b8;">
            {{ $users->total() }} utilisateur(s)
        </span>
        <a href="{{ route('users.create') }}" class="btn-primary btn-sm">
            <i class="fas fa-plus"></i>
            Nouvel utilisateur
        </a>
    </div>
</div>

{{-- Table --}}
<div class="card" style="overflow:hidden;">
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Créé le</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                @php
                    $userRole    = $user->roles->first()?->name;
                    $roleInfo    = $roleLabels[$userRole] ?? ['label' => ucfirst($userRole ?? '—'), 'color' => '#64748b', 'bg' => '#f1f5f9'];
                    $initials    = strtoupper(substr($user->name, 0, 1));
                    $isSelf      = $user->id === auth()->id();
                @endphp
                <tr>
                    <td style="color:#94a3b8; font-size:0.8125rem; font-weight:500;">#{{ $user->id }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:0.625rem;">
                            <div style="
                                width: 36px; height: 36px;
                                background: linear-gradient(135deg, #4f46e5, #7c3aed);
                                border-radius: 50%;
                                display: flex; align-items: center; justify-content: center;
                                color: white; font-size: 0.875rem; font-weight: 700; flex-shrink: 0;
                            ">
                                {{ $initials }}
                            </div>
                            <div>
                                <div style="font-weight:500; font-size:0.875rem; color:#0f172a;">
                                    {{ $user->name }}
                                    @if($isSelf)
                                    <span style="font-size:0.6875rem; color:#4f46e5; font-weight:600; margin-left:0.25rem;">(vous)</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:0.875rem; color:#374151;">{{ $user->email }}</td>
                    <td style="font-size:0.875rem; color:#64748b;">{{ $user->phone ?? '—' }}</td>
                    <td>
                        @if($userRole)
                        <span style="
                            display:inline-block;
                            padding: 0.2rem 0.6rem;
                            background: {{ $roleInfo['bg'] }};
                            color: {{ $roleInfo['color'] }};
                            border-radius: 9999px;
                            font-size: 0.75rem;
                            font-weight: 600;
                        ">{{ $roleInfo['label'] }}</span>
                        @else
                        <span style="color:#94a3b8; font-size:0.875rem;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($user->is_active)
                        <span style="display:inline-flex; align-items:center; gap:0.25rem; padding:0.2rem 0.6rem; background:#f0fdf4; color:#16a34a; border-radius:9999px; font-size:0.75rem; font-weight:600;">
                            <span style="width:6px; height:6px; background:#16a34a; border-radius:50%;"></span>
                            Actif
                        </span>
                        @else
                        <span style="display:inline-flex; align-items:center; gap:0.25rem; padding:0.2rem 0.6rem; background:#fef2f2; color:#dc2626; border-radius:9999px; font-size:0.75rem; font-weight:600;">
                            <span style="width:6px; height:6px; background:#dc2626; border-radius:50%;"></span>
                            Inactif
                        </span>
                        @endif
                    </td>
                    <td style="font-size:0.8125rem; color:#94a3b8; white-space:nowrap;">
                        {{ $user->created_at->format('d/m/Y') }}<br>
                        <span style="font-size:0.75rem;">{{ $user->created_at->format('H:i') }}</span>
                    </td>
                    <td>
                        <div x-data="{ confirmDelete: false }" style="display:flex; gap:0.375rem; align-items:center;">
                            <a href="{{ route('users.edit', $user) }}" class="btn-secondary btn-sm">
                                <i class="fas fa-pencil"></i>
                            </a>

                            <form method="POST" action="{{ route('users.toggle', $user) }}" style="display:inline;">
                                @csrf
                                <button type="submit"
                                        class="{{ $user->is_active ? 'btn-warning' : 'btn-success' }} btn-sm"
                                        title="{{ $user->is_active ? 'Désactiver' : 'Activer' }}">
                                    <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                </button>
                            </form>

                            @if(!$isSelf)
                            <div style="position:relative;">
                                <button @click="confirmDelete = true" class="btn-danger btn-sm" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>

                                <div x-show="confirmDelete" x-cloak
                                     style="position:absolute; right:0; top:calc(100% + 6px); z-index:50; width:220px;"
                                     class="dropdown-menu" style="padding:1rem;">
                                    <div style="padding:0.75rem 1rem;">
                                        <p style="font-size:0.8125rem; color:#0f172a; font-weight:500; margin:0 0 0.75rem;">
                                            Supprimer <strong>{{ $user->name }}</strong> ?
                                        </p>
                                        <div style="display:flex; gap:0.5rem;">
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" style="flex:1;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger btn-sm" style="width:100%; justify-content:center;">
                                                    Confirmer
                                                </button>
                                            </form>
                                            <button @click="confirmDelete = false" class="btn-secondary btn-sm" style="flex:1; justify-content:center;">
                                                Annuler
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center; padding:3rem 1rem; color:#94a3b8;">
                        <i class="fas fa-users" style="font-size:2rem; color:#e2e8f0; display:block; margin-bottom:0.75rem;"></i>
                        Aucun utilisateur trouvé pour ce filtre.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="card-footer" style="display:flex; justify-content:center;">
        {{ $users->links() }}
    </div>
    @endif
</div>

@endsection
