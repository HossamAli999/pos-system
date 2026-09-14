<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ur', 'he', 'fa', 'ps']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') @yield('title') &middot; {{ config('app.name', 'POS') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-50:  #eef2ff;
            --brand-100: #e0e7ff;
            --brand-500: #6366f1;
            --brand-600: #4f46e5;
            --brand-700: #4338ca;
            --ink-900: #0f172a;
            --ink-800: #1e293b;
            --ink-700: #334155;
            --ink-500: #64748b;
            --ink-400: #94a3b8;
            --ink-200: #e2e8f0;
            --ink-100: #f1f5f9;
            --ink-50:  #f8fafc;
            --radius-sm: 6px;
            --radius-lg: 16px;
            --shadow-lg: 0 20px 40px rgba(15, 23, 42, .12);
            --font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            min-height: 100vh;
            background: var(--ink-50);
            color: var(--ink-800);
            font-family: var(--font);
            overflow-x: hidden;
        }
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
        }
        body::before,
        body::after {
            content: "";
            position: absolute;
            width: 520px;
            height: 520px;
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
        }
        body::before {
            top: -180px;
            inset-inline-start: -180px;
            background: radial-gradient(circle, rgba(99, 102, 241, .16), transparent 70%);
        }
        body::after {
            bottom: -200px;
            inset-inline-end: -180px;
            background: radial-gradient(circle, rgba(79, 70, 229, .12), transparent 70%);
        }

        .error-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            background: #fff;
            border: 1px solid var(--ink-100);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 40px 36px 32px;
            text-align: center;
        }
        .error-mark {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--brand-500), var(--brand-700));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(79, 70, 229, .3);
        }
        .error-mark svg {
            width: 30px;
            height: 30px;
            stroke: #fff;
        }
        .error-code {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .08em;
            color: var(--brand-600);
            text-transform: uppercase;
            margin: 0 0 8px;
        }
        .error-title {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.02em;
            color: var(--ink-900);
            margin: 0 0 10px;
        }
        .error-message {
            font-size: 14px;
            line-height: 1.6;
            color: var(--ink-500);
            margin: 0 0 28px;
        }
        .error-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-error {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            font-family: inherit;
            transition: filter .15s ease, background .15s ease;
        }
        .btn-error svg { width: 15px; height: 15px; flex-shrink: 0; }
        .btn-error-primary {
            background: var(--brand-600);
            color: #fff;
            box-shadow: 0 8px 18px rgba(79, 70, 229, .28);
        }
        .btn-error-primary:hover { filter: brightness(.95); color: #fff; }
        .btn-error-ghost {
            background: #fff;
            border-color: var(--ink-200);
            color: var(--ink-700);
        }
        .btn-error-ghost:hover { background: var(--ink-50); color: var(--ink-900); }
        .error-footer {
            margin-top: 24px;
            font-size: 12px;
            color: var(--ink-400);
        }
        @media (max-width: 420px) {
            .error-card { padding: 32px 22px 26px; }
            .error-title { font-size: 20px; }
        }
    </style>

    @yield('head')
</head>
<body>
    <div class="error-card">
        <div class="error-mark">
            @yield('icon')
        </div>

        <p class="error-code">Error @yield('code')</p>
        <h1 class="error-title">@yield('title')</h1>
        <p class="error-message">@yield('message')</p>

        <div class="error-actions">
            <a href="{{ url('/') }}" class="btn-error btn-error-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg>
                Dashboard
            </a>
            <a href="javascript:history.back()" class="btn-error btn-error-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18 9 12l6-6"/></svg>
                {{ __('lang_v1.go_back') }}
            </a>
            @yield('extra_action')
        </div>

        <p class="error-footer">{{ config('app.name', 'POS') }}</p>
    </div>
</body>
</html>
