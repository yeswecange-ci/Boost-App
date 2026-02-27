<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle ?? config('app.name', 'Boost Manager') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="{ sidebarOpen: false }">

{{-- ─── SIDEBAR ──────────────────────────────────────────────── --}}
<aside class="sidebar" :class="{ 'open': sidebarOpen }">

    {{-- Logo --}}
    <div style="padding: 1.25rem 1.25rem 1rem; border-bottom: 1px solid var(--color-border);">
        <a href="{{ route('home') }}" style="display:flex; align-items:center; gap:0.75rem; text-decoration:none;">
            <div style="
                width: 36px; height: 36px;
                background: linear-gradient(135deg, #4f46e5, #7c3aed);
                border-radius: 0.625rem;
                display: flex; align-items: center; justify-content: center;
                color: white; font-size: 1rem; flex-shrink: 0;
            ">
                <i class="fas fa-rocket"></i>
            </div>
            <span style="font-size: 1.0625rem; font-weight: 700; color: #0f172a;">Boost Manager</span>
        </a>
    </div>

    {{-- Nav --}}
    <nav style="padding: 1rem 0.75rem; flex: 1;">

        <div style="font-size: 0.6875rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; padding: 0 0.5rem; margin-bottom: 0.5rem;">
            Navigation
        </div>

        <a href="{{ route('home') }}"
           class="sidebar-item {{ request()->routeIs('home') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-house"></i></span>
            Dashboard
        </a>

        <a href="{{ route('posts.index') }}"
           class="sidebar-item {{ request()->routeIs('posts.*') ? 'active' : '' }}">
            <span class="icon"><i class="fab fa-facebook"></i></span>
            Posts Facebook
        </a>

        <a href="{{ route('boost.my-requests') }}"
           class="sidebar-item {{ request()->routeIs('boost.my-requests') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-rocket"></i></span>
            Mes boosts
        </a>

        @if(auth()->user()->hasRole(['admin']))
        <div style="font-size: 0.6875rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; padding: 0 0.5rem; margin: 1rem 0 0.5rem;">
            Administration
        </div>
        <a href="{{ route('settings.index') }}"
           class="sidebar-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-gear"></i></span>
            Paramètres
        </a>
        <a href="{{ route('users.index') }}"
           class="sidebar-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-users"></i></span>
            Utilisateurs
        </a>
        @endif

        @if(auth()->user()->hasRole(['validator_n1', 'validator', 'validator_n2', 'admin']))
        <div style="font-size: 0.6875rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; padding: 0 0.5rem; margin: 1rem 0 0.5rem;">
            Validation
        </div>
        @endif

        @if(auth()->user()->hasRole(['validator_n1', 'validator', 'admin']))
        <a href="{{ route('boost.pending-n1') }}"
           class="sidebar-item {{ request()->routeIs('boost.pending-n1') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-clock"></i></span>
            File N+1
            @php $n1Count = \App\Models\BoostRequest::where('status', 'pending_n1')->count(); @endphp
            @if($n1Count > 0)
            <span style="margin-left:auto; background:#ef4444; color:#fff; font-size:0.6875rem; font-weight:700; padding:0.125rem 0.5rem; border-radius:9999px;">
                {{ $n1Count }}
            </span>
            @endif
        </a>
        @endif

        @if(auth()->user()->hasRole(['validator_n2', 'admin']))
        <a href="{{ route('boost.pending-n2') }}"
           class="sidebar-item {{ request()->routeIs('boost.pending-n2') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-shield-halved"></i></span>
            File N+2
            @php $n2Count = \App\Models\BoostRequest::where('status', 'pending_n2')->count(); @endphp
            @if($n2Count > 0)
            <span style="margin-left:auto; background:#f59e0b; color:#fff; font-size:0.6875rem; font-weight:700; padding:0.125rem 0.5rem; border-radius:9999px;">
                {{ $n2Count }}
            </span>
            @endif
        </a>
        @endif

        @if(auth()->user()->hasRole(['validator_n1', 'validator', 'validator_n2', 'admin']))
        <a href="{{ route('boost.all') }}"
           class="sidebar-item {{ request()->routeIs('boost.all') ? 'active' : '' }}">
            <span class="icon"><i class="fas fa-list"></i></span>
            Historique
        </a>
        @endif

    </nav>

    {{-- User info --}}
    @php $__u = auth()->user(); @endphp
    <div style="padding: 1rem 0.75rem; border-top: 1px solid var(--color-border);">
        <a href="{{ route('profile.show') }}" style="display:flex; align-items:center; gap:0.75rem; padding:0.625rem 0.5rem; border-radius:0.5rem; text-decoration:none; transition:background .15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
            <x-user-avatar :user="$__u" :size="36" />
            <div style="flex:1; min-width:0;">
                <div style="font-size:0.875rem; font-weight:600; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                    {{ $__u->name }}
                </div>
                <div style="font-size:0.75rem; color:#64748b;">
                    {{ $__u->roles->first()?->name ?? 'Utilisateur' }}
                </div>
            </div>
            <i class="fas fa-pen-to-square" style="font-size:0.75rem; color:#94a3b8; flex-shrink:0;"></i>
        </a>
        <form method="POST" action="{{ route('logout') }}" style="margin-top:0.25rem;">
            @csrf
            <button type="submit" class="sidebar-item" style="width:100%; border:none; background:transparent; cursor:pointer; color:#ef4444;">
                <span class="icon"><i class="fas fa-arrow-right-from-bracket"></i></span>
                Déconnexion
            </button>
        </form>
    </div>

</aside>

{{-- ─── MAIN WRAPPER ──────────────────────────────────────────── --}}
<div class="main-wrapper">

    {{-- Top Header --}}
    <header class="top-header">
        {{-- Mobile menu toggle --}}
        <button @click="sidebarOpen = !sidebarOpen"
                class="lg:hidden"
                style="background:none; border:none; cursor:pointer; padding:0.25rem; color:#64748b; font-size:1.25rem;">
            <i class="fas fa-bars"></i>
        </button>

        {{-- Page title --}}
        <div style="flex:1;">
            <h1 style="font-size: 1.0625rem; font-weight: 700; color: #0f172a; margin: 0;">
                @yield('page-title', config('app.name', 'Boost Manager'))
            </h1>
            @hasSection('page-subtitle')
            <p style="font-size: 0.8125rem; color: #64748b; margin: 0.125rem 0 0;">
                @yield('page-subtitle')
            </p>
            @endif
        </div>

        {{-- Notifications --}}
        @php
            $__notifCount  = $__u->unreadNotifications()->count();
            $__notifItems  = $__notifCount > 0 ? $__u->unreadNotifications()->latest()->limit(10)->get() : collect();
        @endphp
        <div x-data="{ open: false }" style="position:relative;">
            <button @click="open = !open" @click.away="open = false"
                    style="position:relative; background:none; border:none; cursor:pointer; width:2.5rem; height:2.5rem; border-radius:0.5rem; display:flex; align-items:center; justify-content:center; color:#64748b; font-size:1.125rem; transition:background 0.15s;"
                    onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                <i class="fas fa-bell"></i>
                @if($__notifCount > 0)
                <span style="position:absolute; top:4px; right:4px; width:8px; height:8px; background:#ef4444; border-radius:50%; border:2px solid white;"></span>
                @endif
            </button>

            <div x-show="open" x-cloak
                 style="position:absolute; right:0; top:calc(100% + 8px); width:360px; max-height:400px; overflow-y:auto; z-index:50;"
                 class="dropdown-menu">
                <div style="padding: 0.625rem 0.875rem; border-bottom: 1px solid var(--color-border); font-weight: 600; font-size: 0.875rem; color: #0f172a;">
                    Notifications
                    @if($__notifCount > 0)
                    <span style="float:right; font-size:0.75rem; color:#64748b; font-weight:normal;">
                        {{ $__notifCount }} non lue(s)
                    </span>
                    @endif
                </div>

                @forelse($__notifItems as $notif)
                <a class="notif-item"
                   href="{{ route('boost.show', $notif->data['boost_id']) }}"
                   onclick="event.preventDefault(); document.getElementById('mark-read-{{ $notif->id }}').submit();">
                    <div style="font-size:0.875rem; font-weight:500; color:#0f172a;">
                        {{ $notif->data['message'] }}
                    </div>
                    <div style="font-size:0.75rem; color:#64748b; margin-top:0.25rem;">
                        {{ $notif->created_at->diffForHumans() }}
                    </div>
                </a>
                <form id="mark-read-{{ $notif->id }}"
                      method="POST"
                      action="{{ route('notifications.read', $notif->id) }}"
                      style="display:none;">@csrf</form>
                @empty
                <div style="padding: 1.25rem; text-align:center; color:#64748b; font-size:0.875rem;">
                    <i class="fas fa-check-circle" style="font-size:1.5rem; color:#16a34a; display:block; margin-bottom:0.5rem;"></i>
                    Aucune nouvelle notification
                </div>
                @endforelse

                @if($__notifCount > 0)
                <div style="padding: 0.5rem 0.875rem; border-top: 1px solid var(--color-border);">
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="dropdown-item" style="width:100%; justify-content:center; color:var(--color-primary); font-weight:500;">
                            Tout marquer comme lu
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        {{-- User avatar dropdown --}}
        <div x-data="{ open: false }" style="position:relative;">
            <button @click="open = !open" @click.away="open = false"
                    style="display:flex; align-items:center; gap:0.5rem; background:none; border:none; cursor:pointer; padding:0.375rem 0.5rem; border-radius:0.5rem; transition:background 0.15s;"
                    onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                <x-user-avatar :user="$__u" :size="32" />
                <span style="font-size:0.875rem; font-weight:500; color:#374151; max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" class="hidden sm:inline">
                    {{ $__u->name }}
                </span>
                <i class="fas fa-chevron-down" style="font-size:0.6875rem; color:#94a3b8;"></i>
            </button>

            <div x-show="open" x-cloak
                 style="position:absolute; right:0; top:calc(100% + 8px); z-index:50;"
                 class="dropdown-menu">
                <div style="padding:0.625rem 0.875rem; border-bottom:1px solid var(--color-border);">
                    <div style="font-weight:600; font-size:0.875rem; color:#0f172a;">{{ $__u->name }}</div>
                    <div style="font-size:0.75rem; color:#64748b;">{{ $__u->email }}</div>
                </div>
                <div style="padding:0.375rem;">
                    <a href="{{ route('profile.show') }}" class="dropdown-item">
                        <i class="fas fa-user-pen"></i>
                        Mon profil
                    </a>
                </div>
                <div style="padding:0 0.375rem 0.375rem; border-top:1px solid var(--color-border);">
                    <form method="POST" action="{{ route('logout') }}" style="margin-top:0.375rem;">
                        @csrf
                        <button type="submit" class="dropdown-item" style="color:#ef4444; width:100%;">
                            <i class="fas fa-arrow-right-from-bracket"></i>
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </header>

    {{-- Mobile sidebar overlay backdrop --}}
    <div x-show="sidebarOpen"
         @click="sidebarOpen = false"
         x-cloak
         style="position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:35;"
         class="lg:hidden"></div>

    {{-- Main Content --}}
    <main class="main-content">
        @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:1.5rem;">
            <i class="fas fa-check-circle" style="margin-right:0.5rem;"></i>
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom:1.5rem;">
            <i class="fas fa-exclamation-circle" style="margin-right:0.5rem;"></i>
            {{ session('error') }}
        </div>
        @endif

        @yield('content')
    </main>

</div>

@stack('scripts')
</body>
</html>
