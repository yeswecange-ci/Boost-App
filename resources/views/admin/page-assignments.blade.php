@extends('layouts.app')

@section('page-title', 'Assignation des pages Facebook')
@section('page-subtitle', 'Définissez quelles pages chaque utilisateur peut voir et gérer')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; align-items:center; justify-content:space-between;">
        <div>
            <h2 style="font-size:1rem; font-weight:600; color:#0f172a; margin:0;">Matrice d'accès</h2>
            <p style="font-size:0.8125rem; color:#64748b; margin:0.25rem 0 0;">
                {{ $users->count() }} utilisateur(s) · {{ $pages->count() }} page(s) active(s)
            </p>
        </div>
    </div>

    <div class="card-body" style="padding:0;">
        @if($users->isEmpty() || $pages->isEmpty())
        <div style="padding:3rem; text-align:center; color:#64748b;">
            <i class="fas fa-sitemap" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:1rem; display:block;"></i>
            @if($users->isEmpty())
                Aucun utilisateur non-admin actif trouvé.
            @else
                Aucune page Facebook active trouvée.
            @endif
        </div>
        @else
        <form method="POST" action="{{ route('page-assignments.update') }}">
            @csrf

            {{-- Scroll horizontal si beaucoup de pages --}}
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:600px;">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:2px solid var(--color-border);">
                            <th style="padding:0.875rem 1rem; text-align:left; font-size:0.8125rem; font-weight:600; color:#475569; white-space:nowrap; min-width:200px;">
                                Utilisateur
                            </th>
                            @foreach($pages as $page)
                            <th style="padding:0.875rem 0.75rem; text-align:center; font-size:0.75rem; font-weight:600; color:#475569; white-space:nowrap; max-width:140px;">
                                <div style="display:flex; flex-direction:column; align-items:center; gap:0.25rem;">
                                    <i class="fab fa-facebook-square" style="font-size:1.125rem; color:#1877f2;"></i>
                                    <span style="overflow:hidden; text-overflow:ellipsis; max-width:130px; display:block;" title="{{ $page->page_name }}">
                                        {{ Str::limit($page->page_name, 18) }}
                                    </span>
                                </div>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        @php
                            $assignedIds = $user->facebookPages->pluck('id')->toArray();
                            $roleName    = $user->roles->first()?->name ?? 'user';
                            $roleColors  = [
                                'operator'     => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'label' => 'Opérateur'],
                                'validator_n1' => ['bg' => '#f0fdf4', 'text' => '#15803d', 'label' => 'Validateur N+1'],
                                'validator_n2' => ['bg' => '#fefce8', 'text' => '#854d0e', 'label' => 'Validateur N+2'],
                                'validator'    => ['bg' => '#fdf4ff', 'text' => '#7e22ce', 'label' => 'Validateur'],
                            ];
                            $rc = $roleColors[$roleName] ?? ['bg' => '#f1f5f9', 'text' => '#475569', 'label' => $roleName];
                        @endphp
                        <tr style="border-bottom:1px solid var(--color-border); transition:background .1s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                            <td style="padding:0.875rem 1rem;">
                                <div style="display:flex; align-items:center; gap:0.625rem;">
                                    <div style="width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#4f46e5,#7c3aed); display:flex; align-items:center; justify-content:center; color:#fff; font-size:0.75rem; font-weight:700; flex-shrink:0;">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-size:0.875rem; font-weight:600; color:#0f172a;">{{ $user->name }}</div>
                                        <span style="display:inline-block; font-size:0.6875rem; font-weight:600; padding:0.125rem 0.5rem; border-radius:9999px; background:{{ $rc['bg'] }}; color:{{ $rc['text'] }};">
                                            {{ $rc['label'] }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            @foreach($pages as $page)
                            <td style="padding:0.875rem 0.75rem; text-align:center;">
                                <label style="cursor:pointer; display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px;">
                                    <input type="checkbox"
                                           name="assignments[{{ $user->id }}][]"
                                           value="{{ $page->id }}"
                                           {{ in_array($page->id, $assignedIds) ? 'checked' : '' }}
                                           style="width:16px; height:16px; accent-color:var(--color-primary); cursor:pointer;">
                                </label>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="padding:1rem 1.25rem; border-top:1px solid var(--color-border); display:flex; align-items:center; justify-content:space-between; gap:1rem; background:#f8fafc;">
                <p style="font-size:0.8125rem; color:#64748b; margin:0;">
                    <i class="fas fa-info-circle" style="margin-right:0.375rem;"></i>
                    Les utilisateurs sans page cochée ne verront aucun post ni campagne.
                </p>
                <button type="submit" class="btn-primary" style="flex-shrink:0;">
                    <i class="fas fa-save" style="margin-right:0.5rem;"></i>
                    Sauvegarder les assignations
                </button>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
