<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Boost Manager' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            display: flex;
        }

        /* ── Left panel ───────────────────────────────── */
        .auth-left {
            position: relative;
            width: 480px;
            flex-shrink: 0;
            background: #0f0c29;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem 3.5rem;
        }

        /* Gradient overlay */
        .auth-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(145deg,
                #1a1060 0%,
                #2d1b69 35%,
                #4c1d95 65%,
                #5b21b6 100%
            );
            z-index: 0;
        }

        /* Decorative blobs */
        .auth-left .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.25;
            pointer-events: none;
            z-index: 1;
        }
        .auth-left .blob-1 {
            width: 380px; height: 380px;
            background: #818cf8;
            top: -120px; right: -100px;
        }
        .auth-left .blob-2 {
            width: 300px; height: 300px;
            background: #a78bfa;
            bottom: -80px; left: -80px;
        }
        .auth-left .blob-3 {
            width: 180px; height: 180px;
            background: #60a5fa;
            top: 45%; left: 55%;
            transform: translate(-50%, -50%);
        }

        /* Grid texture overlay */
        .auth-left .grid-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            background-image:
                linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .auth-left-inner {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            height: 100%;
            gap: 0;
        }

        /* ── Right panel ──────────────────────────────── */
        .auth-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: #ffffff;
            position: relative;
        }

        .auth-right::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 1px;
            height: 100%;
            background: linear-gradient(to bottom, transparent, #e2e8f0 20%, #e2e8f0 80%, transparent);
        }

        .auth-form-container {
            width: 100%;
            max-width: 400px;
        }

        /* ── Input with icon ──────────────────────────── */
        .input-icon-wrap {
            position: relative;
        }

        .input-icon-wrap .input-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.875rem;
            pointer-events: none;
            transition: color 0.2s;
        }

        .input-icon-wrap input {
            padding-left: 2.5rem;
        }

        .input-icon-wrap input:focus + .input-icon,
        .input-icon-wrap input:focus ~ .input-icon {
            color: #4f46e5;
        }

        .input-icon-wrap .input-icon-right {
            position: absolute;
            right: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            font-size: 0.875rem;
            transition: color 0.15s;
        }

        .input-icon-wrap .input-icon-right:hover { color: #64748b; }

        /* ── Auth button ──────────────────────────────── */
        .btn-auth {
            width: 100%;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, #4f46e5, #6d28d9);
            color: #ffffff;
            border: none;
            border-radius: 0.625rem;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            letter-spacing: 0.01em;
            transition: opacity 0.2s, transform 0.1s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Inter', sans-serif;
        }

        .btn-auth:hover {
            opacity: 0.93;
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.45);
            transform: translateY(-1px);
        }

        .btn-auth:active { transform: translateY(0); }

        /* ── Feature item ─────────────────────────────── */
        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 0.875rem;
        }

        .feature-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.8125rem;
            color: #c4b5fd;
        }

        /* ── Stat bar ─────────────────────────────────── */
        .stat-bar {
            display: flex;
            gap: 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 1.5rem;
        }

        .stat-bar-item {
            flex: 1;
            text-align: center;
            padding: 0 0.5rem;
        }

        .stat-bar-item:not(:last-child) {
            border-right: 1px solid rgba(255,255,255,0.1);
        }

        /* ── Form divider ─────────────────────────────── */
        .form-divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.25rem 0;
        }

        .form-divider::before,
        .form-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #f1f5f9;
        }

        @media (max-width: 1023px) {
            .auth-left { display: none !important; }
            .auth-right::before { display: none; }
        }
    </style>
</head>
<body>

{{-- ════════════════════════════════════════════════════════
     LEFT PANEL — Branding
════════════════════════════════════════════════════════ --}}
<div class="auth-left hidden lg:flex">
    {{-- Decorative elements --}}
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="grid-overlay"></div>

    <div class="auth-left-inner">

        {{-- Top — Logo --}}
        <div>
            <div style="display:flex; align-items:center; gap:0.75rem; margin-bottom:3.5rem;">
                <div style="
                    width:42px; height:42px;
                    background: rgba(255,255,255,0.15);
                    border: 1px solid rgba(255,255,255,0.25);
                    border-radius:0.875rem;
                    display:flex; align-items:center; justify-content:center;
                    backdrop-filter:blur(8px);
                ">
                    <i class="fas fa-rocket" style="color:#e0d9ff; font-size:1.125rem;"></i>
                </div>
                <span style="font-size:1.125rem; font-weight:700; color:#ffffff; letter-spacing:-0.01em;">
                    Boost Manager
                </span>
            </div>

            {{-- Headline --}}
            <div style="margin-bottom:2.5rem;">
                <h1 style="font-size:2.125rem; font-weight:700; color:#ffffff; line-height:1.2; margin:0 0 1rem; letter-spacing:-0.02em;">
                    Amplifiez votre<br>
                    <span style="background:linear-gradient(90deg,#a78bfa,#60a5fa); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;">
                        présence sociale
                    </span>
                </h1>
                <p style="font-size:0.9375rem; color:rgba(255,255,255,0.6); line-height:1.7; margin:0; font-weight:400;">
                    Gérez, validez et activez vos campagnes
                    Facebook en quelques clics — sans friction.
                </p>
            </div>

            {{-- Features --}}
            <div style="display:flex; flex-direction:column; gap:1.125rem;">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <div style="font-size:0.9rem; font-weight:600; color:rgba(255,255,255,0.9); margin-bottom:0.125rem;">
                            Automatisation N8N
                        </div>
                        <div style="font-size:0.8125rem; color:rgba(255,255,255,0.5); line-height:1.5;">
                            Création de campagne Meta Ads en un clic via webhook
                        </div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div>
                        <div style="font-size:0.9rem; font-weight:600; color:rgba(255,255,255,0.9); margin-bottom:0.125rem;">
                            Validation hiérarchique
                        </div>
                        <div style="font-size:0.8125rem; color:rgba(255,255,255,0.5); line-height:1.5;">
                            Aucune campagne ne part sans approbation
                        </div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <div style="font-size:0.9rem; font-weight:600; color:rgba(255,255,255,0.9); margin-bottom:0.125rem;">
                            Suivi en temps réel
                        </div>
                        <div style="font-size:0.8125rem; color:rgba(255,255,255,0.5); line-height:1.5;">
                            Reach, dépenses, clics — tout en un tableau de bord
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom — Stats --}}
        <div class="stat-bar">
            <div class="stat-bar-item">
                <div style="font-size:1.375rem; font-weight:700; color:#ffffff; letter-spacing:-0.02em;">
                    100%
                </div>
                <div style="font-size:0.75rem; color:rgba(255,255,255,0.45); margin-top:0.125rem;">
                    Automatisé
                </div>
            </div>
            <div class="stat-bar-item">
                <div style="font-size:1.375rem; font-weight:700; color:#ffffff; letter-spacing:-0.02em;">
                    3 rôles
                </div>
                <div style="font-size:0.75rem; color:rgba(255,255,255,0.45); margin-top:0.125rem;">
                    Opérateur · Validateur · Admin
                </div>
            </div>
            <div class="stat-bar-item">
                <div style="font-size:1.375rem; font-weight:700; color:#ffffff; letter-spacing:-0.02em;">
                    0 clic
                </div>
                <div style="font-size:0.75rem; color:rgba(255,255,255,0.45); margin-top:0.125rem;">
                    Friction réduite
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ════════════════════════════════════════════════════════
     RIGHT PANEL — Form
════════════════════════════════════════════════════════ --}}
<div class="auth-right">

    <div class="auth-form-container">

        {{-- Mobile logo --}}
        <div style="text-align:center; margin-bottom:2.5rem;" class="lg:hidden">
            <div style="
                width:52px; height:52px;
                background:linear-gradient(135deg,#4f46e5,#7c3aed);
                border-radius:1rem;
                display:inline-flex; align-items:center; justify-content:center;
                color:white; font-size:1.375rem; margin-bottom:0.75rem;
                box-shadow: 0 8px 20px rgba(79,70,229,0.35);
            ">
                <i class="fas fa-rocket"></i>
            </div>
            <div style="font-size:1.375rem; font-weight:700; color:#0f172a; letter-spacing:-0.02em;">
                Boost Manager
            </div>
        </div>

        @yield('content')

        {{-- Footer --}}
        <p style="text-align:center; margin-top:3rem; font-size:0.75rem; color:#cbd5e1;">
            © {{ date('Y') }} Boost Manager — Usage interne
        </p>

    </div>
</div>

</body>
</html>
