<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $faviconPath = 'images/nexo-logo.svg';
            $faviconUrl = file_exists(public_path($faviconPath)) ? asset($faviconPath) : null;
            // El login no tiene usuario autenticado: usamos los colores de marca por defecto.
            $themePrimary = '#DA291C';
            $themeSecondary = '#1F2937';
        @endphp

        <title>{{ config('app.name', 'Nexo CRM') }}</title>
        @if ($faviconUrl)
            <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=instrument-serif:400,400i&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            [x-cloak] { display: none !important; }

            :root {
                --crm-primary: {{ $themePrimary }};
                --crm-secondary: {{ $themeSecondary }};
            }

            .lf-shell {
                min-height: 100vh;
                min-height: 100dvh;
                display: grid;
                grid-template-columns: 1.05fr 0.95fr;
                background: color-mix(in srgb, var(--crm-primary) 4%, #ffffff);
                font-family: 'Figtree', sans-serif;
            }

            /* ---------- Panel de marca (izquierda) ---------- */
            .lf-brand {
                position: relative;
                overflow: hidden;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: clamp(2rem, 4vw, 3.75rem);
                color: #fff;
                background:
                    radial-gradient(circle at 78% 18%, color-mix(in srgb, var(--crm-secondary) 38%, transparent), transparent 42%),
                    linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 92%, #000), color-mix(in srgb, var(--crm-secondary) 52%, var(--crm-primary)));
            }

            /* malla de puntos sutil (estética dashboard) */
            .lf-brand::before {
                content: '';
                position: absolute;
                inset: 0;
                background-image: radial-gradient(rgba(255, 255, 255, 0.10) 1px, transparent 1px);
                background-size: 22px 22px;
                -webkit-mask-image: radial-gradient(ellipse at 30% 40%, #000 0%, transparent 75%);
                        mask-image: radial-gradient(ellipse at 30% 40%, #000 0%, transparent 75%);
                opacity: 0.7;
                pointer-events: none;
            }

            /* orbes de luz que derivan lentamente */
            .lf-orb {
                position: absolute;
                border-radius: 9999px;
                filter: blur(60px);
                opacity: 0.55;
                pointer-events: none;
            }
            .lf-orb--1 {
                width: 24rem; height: 24rem;
                top: -6rem; right: -5rem;
                background: radial-gradient(circle, color-mix(in srgb, var(--crm-secondary) 80%, #fff) 0%, transparent 70%);
                animation: lfDrift1 18s ease-in-out infinite;
            }
            .lf-orb--2 {
                width: 20rem; height: 20rem;
                bottom: -7rem; left: -4rem;
                background: radial-gradient(circle, color-mix(in srgb, var(--crm-secondary) 55%, var(--crm-primary)) 0%, transparent 70%);
                animation: lfDrift2 22s ease-in-out infinite;
            }

            @keyframes lfDrift1 {
                0%, 100% { transform: translate(0, 0) scale(1); }
                50%      { transform: translate(-26px, 22px) scale(1.08); }
            }
            @keyframes lfDrift2 {
                0%, 100% { transform: translate(0, 0) scale(1); }
                50%      { transform: translate(24px, -18px) scale(1.06); }
            }

            .lf-brand__inner { position: relative; z-index: 2; }

            .lf-logo-row { display: flex; align-items: center; gap: 0.75rem; }
            .lf-logo-row img { width: 2.75rem; height: 2.75rem; }
            .lf-wordmark {
                font-family: 'Instrument Serif', serif;
                font-size: 1.6rem;
                letter-spacing: 0.01em;
                line-height: 1;
            }

            .lf-headline {
                margin-top: clamp(2.5rem, 8vh, 5rem);
                font-size: clamp(2.4rem, 4.4vw, 3.9rem);
                line-height: 1.02;
                letter-spacing: -0.02em;
                font-weight: 600;
                max-width: 16ch;
            }
            .lf-headline em {
                font-family: 'Instrument Serif', serif;
                font-style: italic;
                font-weight: 400;
                color: color-mix(in srgb, var(--crm-secondary) 70%, #ffffff);
            }
            .lf-subtext {
                margin-top: 1.25rem;
                max-width: 42ch;
                font-size: 0.98rem;
                line-height: 1.6;
                color: rgba(255, 255, 255, 0.78);
            }

            .lf-foot {
                position: relative;
                z-index: 2;
                font-size: 0.8rem;
                color: rgba(255, 255, 255, 0.6);
            }
            .lf-foot strong { color: rgba(255, 255, 255, 0.85); font-weight: 600; }

            /* ---------- Lado del formulario (derecha) ---------- */
            .lf-form-side {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: clamp(1.5rem, 4vw, 3rem);
            }
            .lf-card {
                width: 100%;
                max-width: 27rem;
            }

            /* ---------- Controles del formulario ---------- */
            .lf-title {
                font-size: 1.65rem;
                font-weight: 600;
                letter-spacing: -0.01em;
                color: color-mix(in srgb, var(--crm-primary) 90%, #0f172a);
            }
            .lf-subtitle {
                margin-top: 0.4rem;
                font-size: 0.95rem;
                color: #64748b;
            }

            .lf-field { margin-top: 1.25rem; }
            .lf-label {
                display: block;
                margin-bottom: 0.4rem;
                font-size: 0.82rem;
                font-weight: 600;
                color: #475569;
            }

            .lf-input-wrap { position: relative; }
            .lf-input-icon {
                position: absolute;
                top: 50%;
                left: 0.9rem;
                transform: translateY(-50%);
                width: 1.05rem;
                height: 1.05rem;
                color: #94a3b8;
                pointer-events: none;
            }
            .lf-input {
                width: 100%;
                padding: 0.72rem 0.9rem 0.72rem 2.6rem;
                font-size: 0.95rem;
                color: #0f172a;
                background: #fff;
                border: 1px solid color-mix(in srgb, var(--crm-primary) 16%, #cbd5e1);
                border-radius: 0.7rem;
                transition: border-color .15s ease, box-shadow .15s ease;
            }
            .lf-input::placeholder { color: #cbd5e1; }
            .lf-input:focus {
                outline: none;
                border-color: color-mix(in srgb, var(--crm-secondary) 70%, var(--crm-primary));
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--crm-secondary) 22%, transparent);
            }
            .lf-input--pw { padding-right: 2.8rem; }

            .lf-toggle {
                position: absolute;
                top: 50%;
                right: 0.6rem;
                transform: translateY(-50%);
                display: inline-flex;
                padding: 0.35rem;
                color: #94a3b8;
                background: transparent;
                border: 0;
                border-radius: 0.5rem;
                cursor: pointer;
                transition: color .15s ease;
            }
            .lf-toggle:hover { color: color-mix(in srgb, var(--crm-primary) 70%, #334155); }
            .lf-toggle svg { width: 1.15rem; height: 1.15rem; }

            .lf-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-top: 1rem;
            }
            .lf-check { display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer; }
            .lf-check input {
                width: 1rem; height: 1rem;
                border-radius: 0.3rem;
                border: 1px solid #cbd5e1;
                accent-color: var(--crm-secondary);
            }
            .lf-check span { font-size: 0.85rem; color: #64748b; }
            .lf-link { font-size: 0.85rem; color: color-mix(in srgb, var(--crm-secondary) 75%, var(--crm-primary)); text-decoration: none; }
            .lf-link:hover { text-decoration: underline; }

            .lf-submit {
                width: 100%;
                margin-top: 1.6rem;
                padding: 0.8rem 1rem;
                font-size: 0.9rem;
                font-weight: 600;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: #fff;
                background: linear-gradient(135deg, var(--crm-primary), var(--crm-secondary));
                border: 1px solid transparent;
                border-radius: 0.7rem;
                cursor: pointer;
                box-shadow: 0 14px 30px -18px color-mix(in srgb, var(--crm-primary) 80%, #000);
                transition: filter .15s ease, transform .15s ease;
            }
            .lf-submit:hover { filter: brightness(1.05); }
            .lf-submit:active { transform: translateY(1px); }

            .lf-status {
                margin-bottom: 1rem;
                padding: 0.6rem 0.8rem;
                font-size: 0.85rem;
                font-weight: 500;
                color: #047857;
                background: #ecfdf5;
                border: 1px solid #a7f3d0;
                border-radius: 0.6rem;
            }
            .lf-error { margin-top: 0.4rem; font-size: 0.82rem; color: #dc2626; }
            .lf-error ul { margin: 0; padding: 0; list-style: none; }

            /* ---------- Entrada animada ---------- */
            .lf-anim { opacity: 0; animation-fill-mode: forwards; animation-timing-function: cubic-bezier(0.16, 1, 0.3, 1); }
            @keyframes lfReveal { 0% { opacity: 0; transform: translateY(22px); filter: blur(10px); } 100% { opacity: 1; transform: translateY(0); filter: blur(0); } }
            @keyframes lfFadeUp { 0% { opacity: 0; transform: translateY(16px); } 100% { opacity: 1; transform: translateY(0); } }
            .lf-reveal { animation: lfReveal 1s forwards; }
            .lf-fade   { animation: lfFadeUp 0.85s forwards; }

            @media (prefers-reduced-motion: reduce) {
                .lf-anim, .lf-orb { animation: none !important; }
                .lf-anim { opacity: 1; filter: none; transform: none; }
            }

            /* ---------- Responsive ---------- */
            @media (max-width: 860px) {
                .lf-shell { grid-template-columns: 1fr; }
                .lf-brand {
                    padding: 1.75rem;
                    min-height: auto;
                }
                .lf-headline, .lf-subtext, .lf-foot { display: none; }
                .lf-form-side { padding: 2rem 1.25rem 3rem; }
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="lf-shell">
            <!-- Panel de marca -->
            <aside class="lf-brand">
                <span class="lf-orb lf-orb--1"></span>
                <span class="lf-orb lf-orb--2"></span>

                <div class="lf-brand__inner">
                    <a href="/" class="lf-logo-row lf-anim lf-fade" style="animation-delay: .1s">
                        <x-application-logo />
                        <span class="lf-wordmark">Nexo CRM</span>
                    </a>

                    <h1 class="lf-headline lf-anim lf-reveal" style="animation-delay: .28s">
                        Convierte cada llamada en un <em>acuerdo</em>.
                    </h1>
                    <p class="lf-subtext lf-anim lf-fade" style="animation-delay: .5s">
                        Campaña Claro B2B — gestiona leads, negocia y cierra ventas
                        desde un solo panel, de la primera llamada a la activación.
                    </p>
                </div>

                <div class="lf-foot lf-anim lf-fade" style="animation-delay: .65s">
                    <strong>Nexo CRM</strong> · Lima, Perú
                </div>
            </aside>

            <!-- Lado del formulario -->
            <main class="lf-form-side">
                <div class="lf-card">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
