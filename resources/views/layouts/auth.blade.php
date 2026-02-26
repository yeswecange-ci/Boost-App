<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Meta Boost') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-brand {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .auth-brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background: #4f46e5;
            border-radius: 10px;
            color: #fff;
            font-size: 1.125rem;
            margin-bottom: 0.875rem;
        }

        .auth-brand-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.01em;
            margin: 0 0 0.25rem;
        }

        .auth-brand-sub {
            font-size: 0.8125rem;
            color: #94a3b8;
            margin: 0;
        }

        .auth-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.05);
            padding: 2rem;
        }

        .auth-card-footer {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
            text-align: center;
        }

        /* Inputs avec icônes */
        .input-wrap {
            position: relative;
        }

        .input-wrap .input-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #cbd5e1;
            font-size: 0.8125rem;
            pointer-events: none;
        }

        .input-wrap input {
            padding-left: 2.25rem;
        }

        .input-wrap .toggle-pw {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #cbd5e1;
            font-size: 0.8125rem;
            padding: 0;
            line-height: 1;
            transition: color 0.15s;
        }

        .input-wrap .toggle-pw:hover { color: #64748b; }

        /* Bouton connexion */
        .btn-auth {
            width: 100%;
            padding: 0.625rem 1rem;
            background: #4f46e5;
            color: #ffffff;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Inter', sans-serif;
            transition: background 0.15s;
        }

        .btn-auth:hover { background: #4338ca; }

        .auth-copyright {
            margin-top: 2rem;
            font-size: 0.75rem;
            color: #cbd5e1;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="auth-brand">
        <div class="auth-brand-logo">
            <i class="fas fa-rocket"></i>
        </div>
        <h1 class="auth-brand-name">{{ config('app.name', 'Meta Boost') }}</h1>
        <p class="auth-brand-sub">Gestion des campagnes Meta Ads</p>
    </div>

    <div class="auth-card">
        @yield('content')

        @hasSection('card-footer')
        <div class="auth-card-footer">
            @yield('card-footer')
        </div>
        @endif
    </div>

    <p class="auth-copyright">© {{ date('Y') }} {{ config('app.name') }} — Usage interne</p>

</body>
</html>
