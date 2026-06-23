<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $user = auth()->user();
            $faviconPath = 'images/nexo-logo.svg';
            $faviconUrl = file_exists(public_path($faviconPath)) ? asset($faviconPath) : null;
        @endphp

        <title>{{ config('app.name', 'Nexo CRM') }}</title>
        @if ($faviconUrl)
            <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @php
            $themePrimary = $user?->crmPrimaryColor() ?? '#DA291C';
            $themeSecondary = $user?->crmSecondaryColor() ?? '#1F2937';
            $themeMode = $user?->crmThemeMode() ?? 'light';
            $profilePhotoUrl = $user?->profilePhotoUrl();
            $userInitials = $user?->initials() ?: 'U';
        @endphp

        <style>
            :root {
                --crm-primary: {{ $themePrimary }};
                --crm-secondary: {{ $themeSecondary }};
            }

            .crm-primary-button {
                background: linear-gradient(135deg, var(--crm-primary), var(--crm-secondary));
                border-color: transparent;
                color: #fff;
            }

            .crm-primary-button:hover,
            .crm-primary-button:focus,
            .crm-primary-button:active {
                filter: brightness(0.97);
            }

            .crm-secondary-button {
                border-color: rgba(148, 163, 184, 0.8);
                color: var(--crm-primary);
            }

            .crm-secondary-button:hover,
            .crm-secondary-button:focus {
                background: rgba(248, 250, 252, 0.95);
            }

            .crm-sidebar-active {
                background: linear-gradient(135deg, var(--crm-primary), var(--crm-secondary));
                color: #fff;
                box-shadow: 0 14px 30px -18px rgba(15, 23, 42, 0.45);
            }

            .crm-profile-avatar {
                background: linear-gradient(135deg, var(--crm-primary), var(--crm-secondary));
                color: #fff;
            }

            .crm-profile-link {
                color: var(--crm-primary);
            }

            .crm-accent-button {
                background: linear-gradient(135deg, var(--crm-primary), var(--crm-secondary));
                border: 1px solid transparent;
                color: #fff;
                box-shadow: 0 12px 24px -18px rgba(15, 23, 42, 0.6);
            }

            .crm-accent-button:hover,
            .crm-accent-button:focus {
                filter: brightness(1.04);
            }

            .crm-accent-outline-button {
                color: var(--crm-primary);
                border: 1px solid color-mix(in srgb, var(--crm-primary) 45%, #cbd5e1);
                background: color-mix(in srgb, var(--crm-primary) 7%, #fff);
            }

            .crm-accent-outline-button:hover,
            .crm-accent-outline-button:focus {
                background: color-mix(in srgb, var(--crm-primary) 12%, #fff);
            }

            .crm-accent-soft-card {
                border-color: color-mix(in srgb, var(--crm-primary) 35%, #cbd5e1) !important;
                background: linear-gradient(180deg, color-mix(in srgb, var(--crm-primary) 5%, #fff), rgba(255,255,255,0.98));
            }

            .crm-accent-border {
                border-color: color-mix(in srgb, var(--crm-secondary) 70%, var(--crm-primary)) !important;
                box-shadow: 0 0 0 1px color-mix(in srgb, var(--crm-secondary) 35%, transparent);
            }

            .crm-panel-hero {
                background:
                    radial-gradient(circle at top right, color-mix(in srgb, var(--crm-secondary) 32%, transparent), transparent 38%),
                    linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 82%, #0f172a), color-mix(in srgb, var(--crm-secondary) 58%, var(--crm-primary)));
                color: #fff;
            }

            .crm-soft-surface {
                border: 1px solid color-mix(in srgb, var(--crm-primary) 16%, #cbd5e1);
                background: linear-gradient(180deg, color-mix(in srgb, var(--crm-primary) 4%, #ffffff), rgba(255,255,255,0.98));
            }

            .crm-neutral-chip {
                border: 1px solid color-mix(in srgb, var(--crm-primary) 20%, #cbd5e1);
                background: color-mix(in srgb, var(--crm-primary) 8%, #ffffff);
                color: color-mix(in srgb, var(--crm-primary) 68%, #0f172a);
            }

            .crm-accent-chip {
                border: 1px solid color-mix(in srgb, var(--crm-secondary) 42%, var(--crm-primary));
                background: linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 12%, #ffffff), color-mix(in srgb, var(--crm-secondary) 14%, #ffffff));
                color: color-mix(in srgb, var(--crm-primary) 82%, #0f172a);
            }

            .text-blue-700,
            .text-blue-800,
            .text-blue-900,
            .text-cyan-600,
            .text-cyan-700,
            .text-cyan-800,
            .text-cyan-900,
            .text-indigo-600,
            .text-indigo-700,
            .text-indigo-800,
            .text-indigo-900,
            .text-emerald-700,
            .text-emerald-800,
            .text-emerald-900,
            .text-green-700,
            .text-green-800,
            .text-amber-700,
            .text-amber-800,
            .text-amber-900 {
                color: color-mix(in srgb, var(--crm-primary) 72%, var(--crm-secondary)) !important;
            }

            .bg-blue-50,
            .bg-cyan-50,
            .bg-indigo-50,
            .bg-emerald-50,
            .bg-green-50,
            .bg-amber-50,
            .bg-blue-50\/50,
            .bg-cyan-50\/60,
            .bg-emerald-50\/60,
            .bg-amber-50\/50 {
                background: linear-gradient(180deg, color-mix(in srgb, var(--crm-primary) 4%, #ffffff), color-mix(in srgb, var(--crm-secondary) 6%, #ffffff)) !important;
            }

            .bg-blue-100,
            .bg-cyan-100,
            .bg-indigo-100,
            .bg-emerald-100,
            .bg-green-100,
            .bg-amber-100 {
                background: linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 12%, #ffffff), color-mix(in srgb, var(--crm-secondary) 14%, #ffffff)) !important;
            }

            .border-blue-100,
            .border-blue-200,
            .border-blue-300,
            .border-blue-500,
            .border-cyan-100,
            .border-cyan-200,
            .border-indigo-100,
            .border-indigo-200,
            .border-emerald-200,
            .border-emerald-300,
            .border-green-100,
            .border-green-200,
            .border-amber-200,
            .border-amber-300,
            .ring-blue-200,
            .ring-sky-200,
            .ring-amber-200 {
                border-color: color-mix(in srgb, var(--crm-secondary) 34%, rgba(148, 163, 184, 0.28)) !important;
            }

            .bg-blue-500,
            .bg-indigo-600,
            .bg-emerald-600,
            .bg-cyan-500,
            .bg-green-600 {
                background: linear-gradient(135deg, var(--crm-primary), var(--crm-secondary)) !important;
                color: #ffffff !important;
                border-color: transparent !important;
            }

            .text-blue-100,
            .text-cyan-100,
            .text-emerald-100,
            .text-amber-100,
            .text-emerald-50 {
                color: rgba(255, 255, 255, 0.76) !important;
            }

            .crm-tab-active {
                border-color: color-mix(in srgb, var(--crm-secondary) 70%, var(--crm-primary)) !important;
                background: color-mix(in srgb, var(--crm-primary) 10%, #fff) !important;
                color: color-mix(in srgb, var(--crm-primary) 82%, #0f172a) !important;
            }

            .crm-tab-muted {
                background: rgba(241, 245, 249, 0.95);
                color: #475569;
            }

            .crm-tab-muted:hover,
            .crm-tab-muted:focus {
                background: color-mix(in srgb, var(--crm-primary) 10%, #f8fafc);
                color: color-mix(in srgb, var(--crm-primary) 70%, #0f172a);
            }

            .crm-tmo-badge {
                border-color: color-mix(in srgb, var(--crm-secondary) 28%, #cbd5e1);
                background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), color-mix(in srgb, var(--crm-primary) 4%, #ffffff));
            }

            .crm-tmo-badge__eyebrow {
                color: color-mix(in srgb, var(--crm-primary) 62%, #64748b);
            }

            .crm-tmo-badge__module {
                color: color-mix(in srgb, var(--crm-primary) 82%, #0f172a);
            }

            .crm-tmo-badge__timer {
                color: color-mix(in srgb, var(--crm-primary) 74%, var(--crm-secondary));
            }

            .crm-tmo-badge--alert {
                border-color: color-mix(in srgb, #f59e0b 55%, var(--crm-secondary)) !important;
                box-shadow: 0 18px 40px -28px rgba(245, 158, 11, 0.5);
            }

            .crm-main-content > .py-8,
            .crm-main-content > .py-6 {
                padding-top: 0.75rem;
            }

            body.crm-dark-theme {
                background: #020617;
                color: #e5eefb;
                --crm-dark-base: #020617;
                --crm-dark-surface: #0f172a;
                --crm-dark-surface-soft: #111c34;
                --crm-dark-surface-elevated: #16213d;
                --crm-dark-border: rgba(148, 163, 184, 0.18);
                --crm-dark-text: #f8fbff;
                --crm-dark-muted: #b6c3d7;
                --crm-dark-subtle: #8ea0ba;
            }

            body.crm-dark-theme .min-h-screen.bg-gray-100 {
                background:
                    radial-gradient(circle at top, rgba(34, 211, 238, 0.08), transparent 30%),
                    linear-gradient(180deg, #020617 0%, #081122 100%) !important;
            }

            body.crm-dark-theme .bg-white {
                background-color: var(--crm-dark-surface) !important;
            }

            body.crm-dark-theme .bg-slate-50,
            body.crm-dark-theme .bg-gray-50 {
                background-color: var(--crm-dark-surface-soft) !important;
            }

            body.crm-dark-theme .bg-white\/90,
            body.crm-dark-theme .bg-white\/10,
            body.crm-dark-theme .bg-white\/5 {
                background-color: rgba(17, 28, 52, 0.88) !important;
                color: var(--crm-dark-text) !important;
            }

            body.crm-dark-theme .border-slate-200,
            body.crm-dark-theme .border-slate-300,
            body.crm-dark-theme .border-gray-200,
            body.crm-dark-theme .border-gray-300,
            body.crm-dark-theme .ring-slate-200,
            body.crm-dark-theme .ring-black\/5 {
                border-color: var(--crm-dark-border) !important;
            }

            body.crm-dark-theme .shadow,
            body.crm-dark-theme .shadow-sm,
            body.crm-dark-theme .shadow-2xl {
                box-shadow: 0 24px 46px -28px rgba(2, 6, 23, 0.96) !important;
            }

            body.crm-dark-theme .text-slate-900,
            body.crm-dark-theme .text-slate-800,
            body.crm-dark-theme .text-gray-900,
            body.crm-dark-theme .text-gray-800 {
                color: var(--crm-dark-text) !important;
            }

            body.crm-dark-theme .text-slate-700,
            body.crm-dark-theme .text-slate-600,
            body.crm-dark-theme .text-gray-700,
            body.crm-dark-theme .text-gray-600 {
                color: var(--crm-dark-muted) !important;
            }

            body.crm-dark-theme .text-slate-500,
            body.crm-dark-theme .text-slate-400,
            body.crm-dark-theme .text-gray-500,
            body.crm-dark-theme .text-gray-400 {
                color: var(--crm-dark-subtle) !important;
            }

            body.crm-dark-theme aside.lg\:bg-white,
            body.crm-dark-theme header.bg-white,
            body.crm-dark-theme .border-b.border-slate-200.bg-white.lg\:hidden,
            body.crm-dark-theme .relative.flex.h-full.w-72.max-w-\[85vw\].flex-col.bg-white.shadow-2xl {
                background-color: rgba(8, 17, 34, 0.96) !important;
                backdrop-filter: blur(12px);
            }

            body.crm-dark-theme .hover\:bg-slate-50:hover,
            body.crm-dark-theme .hover\:bg-slate-100:hover {
                background-color: rgba(22, 33, 61, 0.95) !important;
            }

            body.crm-dark-theme .crm-secondary-button,
            body.crm-dark-theme .crm-profile-link,
            body.crm-dark-theme button:not(.crm-primary-button):not(.crm-sidebar-active) {
                color: var(--crm-dark-text);
            }

            body.crm-dark-theme input,
            body.crm-dark-theme select,
            body.crm-dark-theme textarea {
                background-color: rgba(8, 17, 34, 0.92) !important;
                border-color: rgba(148, 163, 184, 0.22) !important;
                color: var(--crm-dark-text) !important;
            }

            body.crm-dark-theme input::placeholder,
            body.crm-dark-theme textarea::placeholder {
                color: var(--crm-dark-subtle) !important;
            }

            body.crm-dark-theme .bg-gray-100 {
                background-color: rgba(17, 28, 52, 0.92) !important;
                color: var(--crm-dark-muted) !important;
            }

            body.crm-dark-theme .ring-red-100,
            body.crm-dark-theme .bg-red-50 {
                background-color: rgba(127, 29, 29, 0.18) !important;
                border-color: rgba(248, 113, 113, 0.25) !important;
            }

            body.crm-dark-theme .crm-profile-avatar {
                box-shadow: 0 12px 30px -18px rgba(6, 182, 212, 0.55);
            }

            body.crm-dark-theme .crm-sidebar-active {
                box-shadow: 0 16px 34px -20px rgba(6, 182, 212, 0.4);
            }

            body.crm-dark-theme .crm-accent-button {
                box-shadow: 0 16px 32px -22px rgba(6, 182, 212, 0.35);
            }

            body.crm-dark-theme .crm-accent-outline-button {
                background: rgba(8, 17, 34, 0.92);
                color: #f8fbff !important;
                border-color: color-mix(in srgb, var(--crm-secondary) 30%, rgba(148, 163, 184, 0.3));
            }

            body.crm-dark-theme .crm-accent-outline-button:hover,
            body.crm-dark-theme .crm-accent-outline-button:focus {
                background: rgba(17, 28, 52, 0.98) !important;
            }

            body.crm-dark-theme .crm-accent-soft-card {
                background: linear-gradient(180deg, rgba(17, 28, 52, 0.94), rgba(12, 22, 40, 0.98)) !important;
            }

            body.crm-dark-theme .crm-panel-hero {
                background:
                    radial-gradient(circle at top right, color-mix(in srgb, var(--crm-secondary) 20%, transparent), transparent 42%),
                    linear-gradient(135deg, rgba(15, 23, 42, 0.96), color-mix(in srgb, var(--crm-primary) 24%, rgba(8, 17, 34, 0.98)), color-mix(in srgb, var(--crm-secondary) 28%, rgba(8, 17, 34, 0.98))) !important;
                color: #ffffff !important;
            }

            body.crm-dark-theme .crm-soft-surface {
                background: linear-gradient(180deg, rgba(17, 28, 52, 0.96), rgba(8, 17, 34, 0.98)) !important;
                border-color: color-mix(in srgb, var(--crm-secondary) 22%, rgba(148, 163, 184, 0.26)) !important;
            }

            body.crm-dark-theme .rounded-xl.border.border-gray-200,
            body.crm-dark-theme .rounded-2xl.border.border-gray-200,
            body.crm-dark-theme .rounded-xl.border.border-slate-200,
            body.crm-dark-theme .rounded-2xl.border.border-slate-200,
            body.crm-dark-theme .rounded-xl.border.border-gray-300,
            body.crm-dark-theme .rounded-2xl.border.border-gray-300,
            body.crm-dark-theme .rounded-lg.border.border-gray-200,
            body.crm-dark-theme .rounded-lg.border.border-slate-200 {
                background: linear-gradient(180deg, rgba(17, 28, 52, 0.96), rgba(8, 17, 34, 0.98)) !important;
                border-color: rgba(148, 163, 184, 0.18) !important;
            }

            body.crm-dark-theme .border.border-dashed.border-gray-300,
            body.crm-dark-theme .rounded-xl.border.border-dashed.border-gray-300,
            body.crm-dark-theme .rounded-2xl.border.border-dashed.border-gray-300 {
                background: rgba(8, 17, 34, 0.62) !important;
                border-color: rgba(148, 163, 184, 0.22) !important;
                color: var(--crm-dark-subtle) !important;
            }

            body.crm-dark-theme .crm-neutral-chip {
                background: rgba(17, 28, 52, 0.96) !important;
                border-color: color-mix(in srgb, var(--crm-secondary) 20%, rgba(148, 163, 184, 0.28)) !important;
                color: #f8fbff !important;
            }

            body.crm-dark-theme .crm-accent-chip {
                background: linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 26%, rgba(8, 17, 34, 0.98)), color-mix(in srgb, var(--crm-secondary) 22%, rgba(8, 17, 34, 0.98))) !important;
                border-color: color-mix(in srgb, var(--crm-secondary) 32%, rgba(148, 163, 184, 0.28)) !important;
                color: #ffffff !important;
            }

            body.crm-dark-theme .bg-blue-50,
            body.crm-dark-theme .bg-cyan-50,
            body.crm-dark-theme .bg-indigo-50,
            body.crm-dark-theme .bg-emerald-50,
            body.crm-dark-theme .bg-green-50,
            body.crm-dark-theme .bg-amber-50,
            body.crm-dark-theme .bg-blue-50\/50,
            body.crm-dark-theme .bg-cyan-50\/60,
            body.crm-dark-theme .bg-emerald-50\/60,
            body.crm-dark-theme .bg-amber-50\/50,
            body.crm-dark-theme .bg-blue-100,
            body.crm-dark-theme .bg-cyan-100,
            body.crm-dark-theme .bg-indigo-100,
            body.crm-dark-theme .bg-emerald-100,
            body.crm-dark-theme .bg-green-100,
            body.crm-dark-theme .bg-amber-100 {
                background: linear-gradient(180deg, rgba(17, 28, 52, 0.96), rgba(8, 17, 34, 0.98)) !important;
            }

            body.crm-dark-theme .border-blue-100,
            body.crm-dark-theme .border-blue-200,
            body.crm-dark-theme .border-blue-300,
            body.crm-dark-theme .border-blue-500,
            body.crm-dark-theme .border-cyan-100,
            body.crm-dark-theme .border-cyan-200,
            body.crm-dark-theme .border-indigo-100,
            body.crm-dark-theme .border-indigo-200,
            body.crm-dark-theme .border-emerald-200,
            body.crm-dark-theme .border-emerald-300,
            body.crm-dark-theme .border-green-100,
            body.crm-dark-theme .border-green-200,
            body.crm-dark-theme .border-amber-200,
            body.crm-dark-theme .border-amber-300,
            body.crm-dark-theme .ring-blue-200,
            body.crm-dark-theme .ring-sky-200,
            body.crm-dark-theme .ring-amber-200 {
                border-color: color-mix(in srgb, var(--crm-secondary) 30%, rgba(148, 163, 184, 0.28)) !important;
            }

            body.crm-dark-theme .crm-tab-active {
                background: linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 26%, #0f172a), color-mix(in srgb, var(--crm-secondary) 26%, #0f172a)) !important;
                color: #ffffff !important;
            }

            body.crm-dark-theme .crm-tab-muted {
                background: rgba(15, 23, 42, 0.96) !important;
                color: var(--crm-dark-muted) !important;
                border-color: rgba(148, 163, 184, 0.18) !important;
            }

            body.crm-dark-theme .crm-tab-muted:hover,
            body.crm-dark-theme .crm-tab-muted:focus {
                background: rgba(22, 33, 61, 0.98) !important;
                color: #ffffff !important;
            }

            body.crm-dark-theme .crm-promo-card,
            body.crm-dark-theme .crm-promo-library-card {
                background: linear-gradient(180deg, rgba(17, 28, 52, 0.96), rgba(8, 17, 34, 0.98)) !important;
                border-color: color-mix(in srgb, var(--crm-secondary) 28%, rgba(148, 163, 184, 0.28)) !important;
            }

            body.crm-dark-theme .crm-promo-card:hover,
            body.crm-dark-theme .crm-promo-card:focus,
            body.crm-dark-theme .crm-promo-library-card:hover,
            body.crm-dark-theme .crm-promo-library-card:focus,
            body.crm-dark-theme .crm-promo-library-card--active {
                background: linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 22%, rgba(8, 17, 34, 0.98)), color-mix(in srgb, var(--crm-secondary) 18%, rgba(8, 17, 34, 0.98))) !important;
                border-color: color-mix(in srgb, var(--crm-secondary) 42%, rgba(148, 163, 184, 0.34)) !important;
            }

            body.crm-dark-theme .crm-promo-card__badge,
            body.crm-dark-theme .crm-promo-library-card__badge {
                color: color-mix(in srgb, var(--crm-secondary) 64%, #f8fbff) !important;
            }

            body.crm-dark-theme .crm-promo-card__title,
            body.crm-dark-theme .crm-promo-library-card__title {
                color: #f8fbff !important;
            }

            body.crm-dark-theme .crm-promo-card__hint,
            body.crm-dark-theme .crm-promo-library-card__hint {
                color: var(--crm-dark-subtle) !important;
            }

            body.crm-dark-theme .crm-promo-card__action,
            body.crm-dark-theme .crm-promo-library-card__icon {
                background: rgba(8, 17, 34, 0.92) !important;
                border-color: color-mix(in srgb, var(--crm-secondary) 34%, rgba(148, 163, 184, 0.32)) !important;
                color: color-mix(in srgb, var(--crm-secondary) 72%, #f8fbff) !important;
            }

            body.crm-dark-theme .crm-pdf-popup {
                border-color: color-mix(in srgb, var(--crm-secondary) 46%, rgba(148, 163, 184, 0.48)) !important;
                box-shadow:
                    0 0 0 1px color-mix(in srgb, var(--crm-secondary) 18%, transparent),
                    0 28px 60px -34px rgba(2, 6, 23, 0.92),
                    0 18px 42px -30px color-mix(in srgb, var(--crm-primary) 28%, transparent) !important;
            }

            body.crm-dark-theme .crm-pdf-popup__resize {
                width: 18px;
                height: 18px;
                border-right: 2px solid color-mix(in srgb, var(--crm-secondary) 72%, #cbd5e1);
                border-bottom: 2px solid color-mix(in srgb, var(--crm-secondary) 72%, #cbd5e1);
                border-bottom-right-radius: 0.9rem;
                background:
                    linear-gradient(135deg, transparent 0 56%, color-mix(in srgb, var(--crm-secondary) 78%, #cbd5e1) 56% 60%, transparent 60% 100%),
                    linear-gradient(135deg, transparent 0 72%, color-mix(in srgb, var(--crm-secondary) 56%, #94a3b8) 72% 76%, transparent 76% 100%);
                opacity: 0.95;
            }

            body.crm-dark-theme .bg-black.text-white,
            body.crm-dark-theme button.bg-black.text-white,
            body.crm-dark-theme a.bg-black.text-white {
                background: linear-gradient(135deg, var(--crm-primary), var(--crm-secondary)) !important;
                border-color: transparent !important;
                color: #ffffff !important;
            }

            body.crm-dark-theme button:not(.crm-primary-button):not(.crm-sidebar-active):not(.crm-accent-button):not(.crm-accent-outline-button):not(.crm-tab-active):not(.crm-tab-muted),
            body.crm-dark-theme a.inline-flex.border,
            body.crm-dark-theme button.inline-flex.border {
                color: var(--crm-dark-text) !important;
            }

            body.crm-dark-theme .border-gray-300.text-gray-700,
            body.crm-dark-theme .border-gray-900.text-gray-900,
            body.crm-dark-theme .border-2.border-gray-900.text-gray-900 {
                color: #f8fbff !important;
                border-color: color-mix(in srgb, var(--crm-secondary) 28%, rgba(148, 163, 184, 0.34)) !important;
                background: rgba(8, 17, 34, 0.92) !important;
            }

            body.crm-dark-theme .border-gray-300.text-gray-700:hover,
            body.crm-dark-theme .border-gray-900.text-gray-900:hover,
            body.crm-dark-theme .border-2.border-gray-900.text-gray-900:hover {
                background: rgba(17, 28, 52, 0.98) !important;
            }

            body.crm-dark-theme .border-emerald-600.text-emerald-700,
            body.crm-dark-theme .bg-emerald-600.text-white {
                background: linear-gradient(135deg, var(--crm-primary), var(--crm-secondary)) !important;
                border-color: transparent !important;
                color: #ffffff !important;
            }

            body.crm-dark-theme .bg-emerald-50,
            body.crm-dark-theme .bg-emerald-50\/60,
            body.crm-dark-theme .border-emerald-200 {
                background: rgba(17, 28, 52, 0.92) !important;
                border-color: color-mix(in srgb, var(--crm-secondary) 28%, rgba(148, 163, 184, 0.26)) !important;
                color: #dbeafe !important;
            }

            body.crm-dark-theme .text-emerald-800,
            body.crm-dark-theme .text-emerald-700 {
                color: #dbeafe !important;
            }

            body.crm-dark-theme .text-xs.text-gray-500,
            body.crm-dark-theme .text-sm.text-gray-500,
            body.crm-dark-theme .text-xs.text-gray-600,
            body.crm-dark-theme .text-sm.text-gray-600,
            body.crm-dark-theme .text-xs.text-slate-500,
            body.crm-dark-theme .text-sm.text-slate-500,
            body.crm-dark-theme .text-xs.text-slate-600,
            body.crm-dark-theme .text-sm.text-slate-600 {
                color: var(--crm-dark-subtle) !important;
            }

            body.crm-dark-theme .font-semibold.text-gray-700,
            body.crm-dark-theme .font-medium.text-gray-700,
            body.crm-dark-theme .font-semibold.text-slate-700,
            body.crm-dark-theme .font-medium.text-slate-700 {
                color: var(--crm-dark-text) !important;
            }

            body.crm-dark-theme .crm-tmo-badge {
                background: linear-gradient(180deg, rgba(17, 28, 52, 0.96), rgba(8, 17, 34, 0.98)) !important;
                border-color: rgba(148, 163, 184, 0.18) !important;
                box-shadow: 0 24px 42px -30px rgba(2, 6, 23, 0.95);
            }

            body.crm-dark-theme .crm-tmo-badge__eyebrow {
                color: #9fb4cf !important;
            }

            body.crm-dark-theme .crm-tmo-badge__module,
            body.crm-dark-theme .crm-tmo-badge__timer {
                color: #f8fbff !important;
            }
        </style>
    </head>
    <body class="font-sans antialiased {{ $themeMode === 'dark' ? 'crm-dark-theme' : '' }}">
        @php
            $isBootstrapAdmin = $user && in_array($user->email, config('admin.bootstrap_admin_emails', []), true);
            $isExecutive = $user && method_exists($user, 'hasRole') && $user->hasRole('Ejecutivo');
            $isSupervisor = $user && method_exists($user, 'hasRole') && $user->hasRole('Supervisor');
            $isAdministrator = $user && method_exists($user, 'hasRole') && $user->hasRole('Administrador');
            $isManagement = $user && method_exists($user, 'hasRole') && $user->hasRole('Gerencia');
            $isPostSale = $user && method_exists($user, 'hasRole') && $user->hasRole('Postventa');
            $isValidation = $user && method_exists($user, 'hasRole') && $user->hasRole('Mesa de Control');
            $isHumanResources = $user && method_exists($user, 'hasRole') && $user->hasRole('RRHH');
            $isMarketing = $user && method_exists($user, 'hasRole') && $user->hasRole('MKT');
            $isPromotionAdministrator = $user && method_exists($user, 'hasRole') && $user->hasRole('administrador de promociones');
            $showInternalMessagePopups = $user && method_exists($user, 'hasRole') && ($user->hasRole('Ejecutivo') || $user->hasRole('Supervisor'));
            $adminHomeRoute = $isAdministrator ? route('admin.users.index') : route('admin.dashboard');
            $sidebarConfig = null;
            $sidebarIcon = static function (?string $icon): string {
                return match ($icon) {
                    'phone' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M22 16.92v3a2 2 0 01-2.18 2 19.86 19.86 0 01-8.63-3.07A19.5 19.5 0 014.15 12.8 19.86 19.86 0 011.08 4.11 2 2 0 013.06 2h3a2 2 0 012 1.72c.12.9.33 1.79.62 2.64a2 2 0 01-.45 2.11L7.01 9.7a16 16 0 007.29 7.29l1.23-1.22a2 2 0 012.11-.45c.85.29 1.74.5 2.64.62A2 2 0 0122 16.92z"/></svg>',
                    'clipboard' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6a1 1 0 011 1v2H8V4a1 1 0 011-1z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6M9 16h4"/></svg>',
                    'wallet' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5A2.5 2.5 0 015.5 5H19a2 2 0 012 2v1H5.5A2.5 2.5 0 003 10.5v6A2.5 2.5 0 005.5 19H19a2 2 0 002-2v-1"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5A2.5 2.5 0 015.5 8H21v8H5.5A2.5 2.5 0 013 13.5v-3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12h.01"/></svg>',
                    'map' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l-6 3V6l6-3m0 15l6 3m-6-18v15m6 3l6-3V3l-6 3m0 15V6"/></svg>',
                    'chart' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 20V10M12 20V4M6 20v-6"/></svg>',
                    'shield-check' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 4v5c0 5-3.5 8-7 9-3.5-1-7-4-7-9V7l7-4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.5 12.5l1.7 1.7 3.8-4"/></svg>',
                    'users' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 00-3-3.87"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 3.13a4 4 0 010 7.75"/></svg>',
                    'megaphone' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11v2a2 2 0 002 2h2l5 4V5l-5 4H5a2 2 0 00-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 9a5 5 0 010 6"/><path stroke-linecap="round" stroke-linejoin="round" d="M19 6a9 9 0 010 12"/></svg>',
                    'upload' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 8l4-4 4 4"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 20h16"/></svg>',
                    'ban' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M5.5 18.5l13-13"/></svg>',
                    'building' => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 21V7l7-4 7 4v14"/><path stroke-linecap="round" stroke-linejoin="round" d="M9 10h.01M15 10h.01M9 14h.01M15 14h.01"/></svg>',
                    default => '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l2 2"/></svg>',
                };
            };

            if ($isExecutive) {
                $sidebarConfig = [
                    'title' => 'Panel Ejecutivo',
                    'subtitle' => 'Nexo CRM',
                    'home_route' => route('work.show'),
                    'items' => [
                        ['label' => 'A negociar', 'route' => route('work.show'), 'active' => request()->routeIs('work.*'), 'icon' => 'phone'],
                        ['label' => 'Mi chamba', 'route' => route('my-work.index'), 'active' => request()->routeIs('my-work.*'), 'icon' => 'clipboard'],
                        ['label' => 'Mis ventas', 'route' => route('my-sales.index'), 'active' => request()->routeIs('my-sales.*'), 'icon' => 'chart'],
                        ['label' => 'Promociones', 'route' => route('executive-promotions.index'), 'active' => request()->routeIs('executive-promotions.*'), 'icon' => 'clipboard'],
                        ['label' => 'Mi cobertura', 'route' => route('executive-coverage.index'), 'active' => request()->routeIs('executive-coverage.*'), 'icon' => 'map'],
                        ['label' => 'Mi billete', 'route' => '#', 'active' => false, 'icon' => 'wallet'],
                    ],
                    'show_notifications' => true,
                ];
            } elseif ($isSupervisor) {
                $sidebarConfig = [
                    'title' => 'Panel Supervisor',
                    'subtitle' => 'Nexo CRM',
                    'home_route' => route('supervisor.agreements.index'),
                    'items' => [
                        ['label' => 'Dashboard equipo', 'route' => route('supervisor.dashboard.index'), 'active' => request()->routeIs('supervisor.dashboard.*'), 'icon' => 'chart'],
                        ['label' => 'Validar acuerdos', 'route' => route('supervisor.agreements.index'), 'active' => request()->routeIs('supervisor.agreements.*'), 'icon' => 'shield-check'],
                        ['label' => 'Mi base equipo', 'route' => route('supervisor.team-base.index'), 'active' => request()->routeIs('supervisor.team-base.*'), 'icon' => 'clipboard'],
                        ['label' => 'Promociones', 'route' => route('supervisor.promotions.index'), 'active' => request()->routeIs('supervisor.promotions.*'), 'icon' => 'clipboard'],
                        ['label' => 'TMO en vivo', 'route' => route('supervisor.tmo.index'), 'active' => request()->routeIs('supervisor.tmo.*'), 'icon' => 'chart'],
                        ['label' => 'Actividad ejecutiva', 'route' => route('supervisor.activity-monitoring.index'), 'active' => request()->routeIs('supervisor.activity-monitoring.*'), 'icon' => 'users'],
                        ['label' => 'Mensajes', 'route' => route('supervisor.internal-messages.index'), 'active' => request()->routeIs('supervisor.internal-messages.*'), 'icon' => 'megaphone'],
                    ],
                    'show_notifications' => true,
                ];
            } elseif ($isBootstrapAdmin) {
                $adminItems = [];

                if (!$isAdministrator) {
                    $adminItems[] = ['label' => 'Dashboard', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard'), 'icon' => 'chart'];
                }

                $adminItems[] = ['label' => 'Usuarios', 'route' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*'), 'icon' => 'users'];
                $adminItems[] = ['label' => 'Campañas', 'route' => route('admin.campaigns.index'), 'active' => request()->routeIs('admin.campaigns.*'), 'icon' => 'megaphone'];
                $adminItems[] = ['label' => 'Promociones PDF', 'route' => route('admin.promotions.index'), 'active' => request()->routeIs('admin.promotions.*'), 'icon' => 'clipboard'];
                $adminItems[] = ['label' => 'Importar leads', 'route' => route('admin.leads.import'), 'active' => request()->routeIs('admin.leads.*'), 'icon' => 'upload'];
                $adminItems[] = ['label' => 'Leads deshabilitados', 'route' => route('admin.disabled-leads.index'), 'active' => request()->routeIs('admin.disabled-leads.*'), 'icon' => 'ban'];
                $adminItems[] = ['label' => 'Mensajes', 'route' => route('admin.internal-messages.index'), 'active' => request()->routeIs('admin.internal-messages.*'), 'icon' => 'megaphone'];

                $sidebarConfig = [
                    'title' => 'Panel Admin',
                    'subtitle' => 'Nexo CRM',
                    'home_route' => $adminHomeRoute,
                    'items' => $adminItems,
                    'show_notifications' => false,
                ];
            } elseif ($isManagement) {
                $sidebarConfig = [
                    'title' => 'Panel Gerencia',
                    'subtitle' => 'Nexo CRM',
                    'home_route' => route('dashboard'),
                    'items' => [
                        ['label' => 'Dashboard', 'route' => route('dashboard'), 'active' => request()->routeIs('dashboard'), 'icon' => 'chart'],
                        ['label' => 'Acuerdos', 'route' => route('management.agreements.index'), 'active' => request()->routeIs('management.agreements.*'), 'icon' => 'clipboard'],
                        ['label' => 'TMO en vivo', 'route' => route('management.tmo.index'), 'active' => request()->routeIs('management.tmo.*'), 'icon' => 'chart'],
                        ['label' => 'Actividad ejecutiva', 'route' => route('management.activity-monitoring.index'), 'active' => request()->routeIs('management.activity-monitoring.*'), 'icon' => 'users'],
                        ['label' => 'Mensajes', 'route' => route('management.internal-messages.index'), 'active' => request()->routeIs('management.internal-messages.*'), 'icon' => 'megaphone'],
                    ],
                    'show_notifications' => false,
                ];
            } elseif ($isPostSale) {
                $sidebarConfig = [
                    'title' => 'Panel Postventa',
                    'subtitle' => 'Nexo CRM',
                    'home_route' => route('post-sale.index'),
                    'items' => [
                        ['label' => 'Gestión', 'route' => route('post-sale.index'), 'active' => request()->routeIs('post-sale.*'), 'icon' => 'clipboard'],
                    ],
                    'show_notifications' => false,
                ];
            } elseif ($isValidation) {
                $sidebarConfig = [
                    'title' => 'Mesa de Control',
                    'subtitle' => 'Nexo CRM',
                    'home_route' => route('validation.index'),
                    'items' => [
                        ['label' => 'Validación', 'route' => route('validation.index'), 'active' => request()->routeIs('validation.*'), 'icon' => 'shield-check'],
                        ['label' => 'Activaciones', 'route' => route('activation-control.index'), 'active' => request()->routeIs('activation-control.*'), 'icon' => 'clipboard'],
                        ['label' => 'Cobertura', 'route' => route('territorial-coverage.index'), 'active' => request()->routeIs('territorial-coverage.*'), 'icon' => 'map'],
                    ],
                    'show_notifications' => false,
                ];
            } elseif ($isHumanResources) {
                $sidebarConfig = [
                    'title' => 'Panel RRHH',
                    'subtitle' => 'Nexo CRM',
                    'home_route' => route('rrhh.surveys.index'),
                    'items' => [
                        ['label' => 'Formularios', 'route' => route('rrhh.surveys.index'), 'active' => request()->routeIs('rrhh.surveys.*'), 'icon' => 'users'],
                    ],
                    'show_notifications' => false,
                ];
            } elseif ($isMarketing) {
                $sidebarConfig = [
                    'title' => 'Panel MKT',
                    'subtitle' => 'Nexo CRM',
                    'home_route' => route('mkt.phrases.index'),
                    'items' => [
                        ['label' => 'Frases', 'route' => route('mkt.phrases.index'), 'active' => request()->routeIs('mkt.phrases.*'), 'icon' => 'megaphone'],
                    ],
                    'show_notifications' => false,
                ];
            } elseif ($isPromotionAdministrator) {
                $sidebarConfig = [
                    'title' => 'Panel Promociones',
                    'subtitle' => 'Nexo CRM',
                    'home_route' => route('promotion-admin.index'),
                    'items' => [
                        ['label' => 'Nombres de promociones', 'route' => route('promotion-admin.index'), 'active' => request()->routeIs('promotion-admin.index', 'promotion-admin.store', 'promotion-admin.update', 'promotion-admin.destroy'), 'icon' => 'clipboard'],
                        ['label' => 'Promociones PDF', 'route' => route('promotion-admin.documents.index'), 'active' => request()->routeIs('promotion-admin.documents.*'), 'icon' => 'clipboard'],
                    ],
                    'show_notifications' => false,
                ];
            }
        @endphp

        <div
            class="min-h-screen bg-gray-100"
            @if($sidebarConfig)
                x-data="{
                    open: false,
                    desktopSidebarOpen: false,
                    desktopSidebarHover: false,
                    init() {
                        const storedSidebarState = window.localStorage.getItem('crm-sidebar-pinned');
                        this.desktopSidebarOpen = storedSidebarState === 'true';
                    },
                    toggleDesktopSidebar() {
                        this.desktopSidebarOpen = !this.desktopSidebarOpen;
                        window.localStorage.setItem('crm-sidebar-pinned', this.desktopSidebarOpen ? 'true' : 'false');
                    }
                }"
            @endif
        >

            @auth
                @if($sidebarConfig)
                    <div>
                        <aside
                            class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:flex lg:flex-col lg:border-r lg:border-slate-200 lg:bg-white lg:transition-all lg:duration-300"
                            :class="(desktopSidebarOpen || desktopSidebarHover) ? 'lg:w-72' : 'lg:w-14'"
                            @mouseenter="if (!desktopSidebarOpen) desktopSidebarHover = true"
                            @mouseleave="desktopSidebarHover = false"
                        >
                            <div class="flex h-20 items-center border-b border-slate-200 px-4" :class="(desktopSidebarOpen || desktopSidebarHover) ? 'justify-between gap-3' : 'flex-col justify-center gap-1.5 px-1.5 py-2'">
                                <a href="{{ $sidebarConfig['home_route'] }}" class="shrink-0">
                                    <x-application-logo class="block h-8 w-auto" />
                                </a>
                                <div x-show="desktopSidebarOpen || desktopSidebarHover" x-transition.opacity>
                                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ $sidebarConfig['subtitle'] }}</div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $sidebarConfig['title'] }}</div>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex shrink-0 items-center justify-center border border-slate-200 text-slate-600 transition hover:bg-slate-50"
                                    :class="(desktopSidebarOpen || desktopSidebarHover) ? 'h-9 w-9 rounded-xl' : 'h-7 w-7 rounded-lg'"
                                    @click="toggleDesktopSidebar()"
                                    :title="desktopSidebarOpen ? 'Dejar barra flotante' : 'Fijar barra lateral'"
                                >
                                    <svg class="transition-transform duration-300" :class="desktopSidebarOpen ? 'h-[18px] w-[18px] -rotate-12 text-slate-900' : 'h-4 w-4 text-slate-500'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 3l7 7-2.5 1.5-2 5.5-2.5-2.5-3.5 3.5a1 1 0 01-1.4 0l-.6-.6a1 1 0 010-1.4l3.5-3.5L6 10l5.5-2L13 5.5 14 3z" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto px-3 py-5">
                                <div class="space-y-2">
                                    @foreach($sidebarConfig['items'] as $item)
                                        <a href="{{ $item['route'] }}"
                                           class="flex rounded-2xl py-3 text-sm font-medium transition {{ $item['active'] ? 'crm-sidebar-active shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                                           :class="(desktopSidebarOpen || desktopSidebarHover) ? 'items-center px-4 justify-start' : 'items-center justify-center px-1 py-2.5'"
                                           title="{{ $item['label'] }}">
                                            <span class="inline-flex items-center justify-center shrink-0" :class="(desktopSidebarOpen || desktopSidebarHover) ? 'mr-3' : ''">
                                                {!! $sidebarIcon($item['icon'] ?? null) !!}
                                            </span>
                                            <span x-show="desktopSidebarOpen || desktopSidebarHover" x-transition.opacity>{{ $item['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>

                                @if($sidebarConfig['show_notifications'])
                                    <div class="mt-8 rounded-3xl border border-slate-200 bg-slate-50 p-4" x-show="desktopSidebarOpen || desktopSidebarHover" x-transition>
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Notificaciones</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $isExecutive ? 'Recordatorios' : 'Mesa de Control' }}</div>
                                            </div>
                                            @if($isExecutive)
                                                <span id="executiveReminderBadge" class="{{ $executiveReminderUnreadCount > 0 ? '' : 'hidden ' }}inline-flex min-w-[1.5rem] h-6 items-center justify-center rounded-full bg-red-500 px-2 text-xs font-semibold text-white">
                                                    {{ $executiveReminderUnreadCount }}
                                                </span>
                                            @elseif($isSupervisor)
                                                <span id="supervisorStatusNotificationBadge" class="{{ $supervisorStatusNotificationUnreadCount > 0 ? '' : 'hidden ' }}inline-flex min-w-[1.5rem] h-6 items-center justify-center rounded-full bg-cyan-600 px-2 text-xs font-semibold text-white">
                                                    {{ $supervisorStatusNotificationUnreadCount }}
                                                </span>
                                            @endif
                                        </div>

                                        @if($isExecutive && $executiveReminderUnreadCount > 0)
                                            <form method="POST" action="{{ route('work.reminder-notifications.read-all') }}" class="mt-3">
                                                @csrf
                                                <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                                    Marcar todas como leidas
                                                </button>
                                            </form>
                                        @elseif($isSupervisor && $supervisorStatusNotificationUnreadCount > 0)
                                            <form method="POST" action="{{ route('supervisor.status-notifications.read-all') }}" class="mt-3">
                                                @csrf
                                                <button type="submit" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                                    Marcar todas como leidas
                                                </button>
                                            </form>
                                        @endif

                                        @if($isExecutive)
                                            <div id="executiveReminderDropdownList" class="mt-4 max-h-72 space-y-3 overflow-y-auto pr-1">
                                                @forelse($executiveReminderNotifications as $notification)
                                                    <div data-reminder-notification-id="{{ $notification->id }}" class="rounded-2xl border border-slate-200 bg-white p-3 {{ $notification->read_at ? '' : 'ring-1 ring-red-100' }}">
                                                        <a href="{{ route('work.reminder-notifications.open', $notification) }}" class="block">
                                                            <div class="text-sm font-semibold text-slate-900">{{ $notification->title }}</div>
                                                            <div class="mt-1 text-xs text-slate-600">{{ $notification->message }}</div>
                                                            <div class="mt-2 text-[11px] text-slate-500">
                                                                {{ optional($notification->notified_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                                            </div>
                                                        </a>
                                                    </div>
                                                @empty
                                                    <div data-empty-reminders class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-5 text-center text-sm text-slate-500">
                                                        Aun no tienes recordatorios guardados.
                                                    </div>
                                                @endforelse
                                            </div>
                                        @elseif($isSupervisor)
                                            <div id="supervisorStatusNotificationDropdownList" class="mt-4 max-h-72 space-y-3 overflow-y-auto pr-1">
                                                @forelse($supervisorStatusNotifications as $notification)
                                                    <div data-supervisor-status-notification-id="{{ $notification->id }}" class="rounded-2xl border border-slate-200 bg-white p-3 {{ $notification->read_at ? '' : 'ring-1 ring-cyan-100' }}">
                                                        <a href="{{ route('supervisor.status-notifications.open', $notification) }}" class="block">
                                                            <div class="flex items-start justify-between gap-3">
                                                                <div class="min-w-0">
                                                                    <div class="text-sm font-semibold text-slate-900">{{ $notification->title }}</div>
                                                                    <div class="mt-1 text-xs text-slate-600">{{ $notification->message }}</div>
                                                                </div>
                                                                <span class="inline-flex shrink-0 items-center rounded-full bg-cyan-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-cyan-700">
                                                                    {{ match ($notification->current_status) {
                                                                        'en_evaluacion', 'pendiente_validacion', 'observado' => 'En evaluación',
                                                                        'activo', 'aprobado' => 'Activo',
                                                                        'rechazado' => 'Rechazado',
                                                                        'entregado' => 'Entregado',
                                                                        default => ucfirst(str_replace('_', ' ', $notification->current_status)),
                                                                    } }}
                                                                </span>
                                                            </div>
                                                            <div class="mt-2 text-[11px] text-slate-500">
                                                                {{ optional($notification->notified_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                                            </div>
                                                        </a>
                                                    </div>
                                                @empty
                                                    <div data-empty-supervisor-status-notifications class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-5 text-center text-sm text-slate-500">
                                                        Aun no tienes avisos de Mesa de Control.
                                                    </div>
                                                @endforelse
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="border-t border-slate-200 px-3 py-4">
                                <div x-show="desktopSidebarOpen || desktopSidebarHover" x-transition>
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 items-center justify-center overflow-hidden rounded-2xl border border-white/50 crm-profile-avatar">
                                            @if($profilePhotoUrl)
                                                <img src="{{ $profilePhotoUrl }}" alt="Foto de perfil" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-sm font-semibold uppercase">{{ $userInitials }}</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-slate-900">{{ Auth::user()->name }}</div>
                                            <div class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</div>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('profile.edit') }}"
                                   class="mt-4 inline-flex items-center justify-center gap-2 rounded-2xl text-sm font-medium transition hover:bg-slate-50 crm-profile-link"
                                   :class="(desktopSidebarOpen || desktopSidebarHover) ? 'w-full border border-slate-300 px-4 py-2' : 'mx-auto h-12 w-12 border-transparent px-0 py-0 shadow-none'">
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.125a7.5 7.5 0 0 1 15 0" />
                                    </svg>
                                    <span x-show="desktopSidebarOpen || desktopSidebarHover" x-transition.opacity>Mi perfil</span>
                                </a>
                                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 rounded-2xl text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                        :class="(desktopSidebarOpen || desktopSidebarHover) ? 'w-full border border-slate-300 px-4 py-2' : 'mx-auto h-12 w-12 border-transparent px-0 py-0 shadow-none'"
                                        :title="(desktopSidebarOpen || desktopSidebarHover) ? 'Cerrar Sesión' : 'Salir'">
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.625A2.625 2.625 0 0 0 13.125 3h-6.75A2.625 2.625 0 0 0 3.75 5.625v12.75A2.625 2.625 0 0 0 6.375 21h6.75a2.625 2.625 0 0 0 2.625-2.625V15" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12h12m0 0-3-3m3 3-3 3" />
                                        </svg>
                                        <span x-show="desktopSidebarOpen || desktopSidebarHover" x-transition.opacity>Cerrar Sesión</span>
                                    </button>
                                </form>
                            </div>
                        </aside>

                        <div class="border-b border-slate-200 bg-white lg:hidden">
                            <div class="flex h-16 items-center justify-between px-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <button @click="open = true" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600">
                                        <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                    </button>
                                    <a href="{{ $sidebarConfig['home_route'] }}">
                                        <x-application-logo class="block h-10 w-auto" />
                                    </a>
                                </div>

                                <div class="flex items-center gap-3">
                                    @if($sidebarConfig['show_notifications'])
                                        <div class="relative">
                                            @if($isExecutive)
                                                <span id="executiveReminderBadgeMobile" class="{{ $executiveReminderUnreadCount > 0 ? '' : 'hidden ' }}absolute -top-1 -right-1 inline-flex min-w-[1.2rem] h-5 items-center justify-center rounded-full bg-red-500 px-1 text-[11px] font-semibold text-white">
                                                    {{ $executiveReminderUnreadCount }}
                                                </span>
                                            @elseif($isSupervisor)
                                                <span id="supervisorStatusNotificationBadgeMobile" class="{{ $supervisorStatusNotificationUnreadCount > 0 ? '' : 'hidden ' }}absolute -top-1 -right-1 inline-flex min-w-[1.2rem] h-5 items-center justify-center rounded-full bg-cyan-600 px-1 text-[11px] font-semibold text-white">
                                                    {{ $supervisorStatusNotificationUnreadCount }}
                                                </span>
                                            @endif
                                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600">
                                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9" />
                                                </svg>
                                            </div>
                                        </div>
                                    @endif
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white shadow-sm">
                                            @if($profilePhotoUrl)
                                                <img src="{{ $profilePhotoUrl }}" alt="Foto de perfil" class="h-full w-full object-cover">
                                            @else
                                                <span class="text-xs font-semibold uppercase text-slate-600">{{ $userInitials }}</span>
                                            @endif
                                        </div>
                                        <div class="text-sm font-medium text-slate-700">{{ Auth::user()->name }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="open" x-transition class="fixed inset-0 z-50 lg:hidden" style="display: none;">
                            <div class="absolute inset-0 bg-slate-950/40" @click="open = false"></div>
                            <div class="relative flex h-full w-72 max-w-[85vw] flex-col bg-white shadow-2xl">
                                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-application-logo class="block h-10 w-auto" />
                                        <div class="text-sm font-semibold text-slate-900">{{ $sidebarConfig['title'] }}</div>
                                    </div>
                                    <button @click="open = false" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600">✕</button>
                                </div>

                                <div class="flex-1 space-y-6 overflow-y-auto px-4 py-6">
                                    <div class="space-y-2">
                                        @foreach($sidebarConfig['items'] as $item)
                                            <a href="{{ $item['route'] }}"
                                               class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition {{ $item['active'] ? 'crm-sidebar-active' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                                <span class="inline-flex items-center justify-center shrink-0">
                                                    {!! $sidebarIcon($item['icon'] ?? null) !!}
                                                </span>
                                                <span>{{ $item['label'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>

                                    @if($sidebarConfig['show_notifications'])
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                            <div class="flex items-center justify-between">
                                                <div class="text-sm font-semibold text-slate-900">Notificaciones</div>
                                                @if($isExecutive)
                                                    <span class="inline-flex min-w-[1.5rem] h-6 items-center justify-center rounded-full bg-red-500 px-2 text-xs font-semibold text-white">
                                                        {{ $executiveReminderUnreadCount }}
                                                    </span>
                                                @elseif($isSupervisor)
                                                    <span class="inline-flex min-w-[1.5rem] h-6 items-center justify-center rounded-full bg-cyan-600 px-2 text-xs font-semibold text-white">
                                                        {{ $supervisorStatusNotificationUnreadCount }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($isExecutive)
                                                <div class="mt-3 max-h-56 space-y-3 overflow-y-auto">
                                                    @forelse($executiveReminderNotifications as $notification)
                                                        <a href="{{ route('work.reminder-notifications.open', $notification) }}" class="block rounded-2xl border border-slate-200 bg-white p-3">
                                                            <div class="text-sm font-semibold text-slate-900">{{ $notification->title }}</div>
                                                            <div class="mt-1 text-xs text-slate-600">{{ $notification->message }}</div>
                                                        </a>
                                                    @empty
                                                        <div class="text-sm text-slate-500">Aun no tienes recordatorios guardados.</div>
                                                    @endforelse
                                                </div>
                                            @elseif($isSupervisor)
                                                <div class="mt-3 max-h-56 space-y-3 overflow-y-auto">
                                                    @forelse($supervisorStatusNotifications as $notification)
                                                        <a href="{{ route('supervisor.status-notifications.open', $notification) }}" class="block rounded-2xl border border-slate-200 bg-white p-3">
                                                            <div class="text-sm font-semibold text-slate-900">{{ $notification->title }}</div>
                                                            <div class="mt-1 text-xs text-slate-600">{{ $notification->message }}</div>
                                                        </a>
                                                    @empty
                                                        <div class="text-sm text-slate-500">Aun no tienes avisos de Mesa de Control.</div>
                                                    @endforelse
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="border-t border-slate-200 px-5 py-4">
                                    <a href="{{ route('profile.edit') }}" class="mb-3 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium transition hover:bg-slate-50 crm-profile-link">
                                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.125a7.5 7.5 0 0 1 15 0" />
                                        </svg>
                                        Mi perfil
                                    </a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700">
                                            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.625A2.625 2.625 0 0 0 13.125 3h-6.75A2.625 2.625 0 0 0 3.75 5.625v12.75A2.625 2.625 0 0 0 6.375 21h6.75a2.625 2.625 0 0 0 2.625-2.625V15" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 12h12m0 0-3-3m3 3-3 3" />
                                            </svg>
                                            Cerrar Sesión
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    @include('layouts.navigation')
                @endif
            @endauth

            @isset($header)
                <header class="bg-white shadow" @if($sidebarConfig) :class="desktopSidebarOpen ? 'lg:pl-72' : 'lg:pl-14'" @endif>
                    <div class="{{ $sidebarConfig ? 'max-w-none px-4 py-6 sm:px-6 lg:px-10' : 'max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8' }}">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main
                class="crm-main-content"
                @if($sidebarConfig)
                    :class="desktopSidebarOpen ? 'lg:pl-72' : 'lg:pl-14'"
                @endif
            >
                {{ $slot }}
            </main>
        </div>

        <script>
            window.crmPopupUtils = window.crmPopupUtils || {
                isDarkTheme() {
                    return document.body.classList.contains('crm-dark-theme');
                },
                popupTone(type = 'default') {
                    const isDark = this.isDarkTheme();

                    if (type === 'internal') {
                        return isDark
                            ? {
                                shell: ['border-slate-700/90', 'bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(9,16,30,0.98))]', 'ring-slate-700/60'],
                                badge: 'bg-slate-100 text-slate-950',
                                title: 'text-slate-50',
                                meta: 'text-slate-400',
                                body: 'text-slate-200',
                                close: 'text-slate-400 hover:text-white',
                                mover: 'border-slate-600 text-slate-300',
                                track: 'bg-slate-800/90',
                            }
                            : {
                                shell: ['border-cyan-200', 'bg-gradient-to-br', 'from-cyan-50', 'via-white', 'to-violet-50', 'ring-cyan-100'],
                                badge: 'bg-slate-900 text-white',
                                title: 'text-slate-900',
                                meta: 'text-slate-500',
                                body: 'text-slate-700',
                                close: 'text-slate-400 hover:text-slate-700',
                                mover: 'border-cyan-200 text-cyan-700',
                                track: 'bg-slate-200/80',
                            };
                    }

                    if (type === 'rrhh') {
                        return isDark
                            ? {
                                shell: ['border-slate-700', 'bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(14,10,26,0.98))]', 'ring-slate-700/60'],
                                badge: 'bg-rose-500 text-white',
                                title: 'text-slate-50',
                                meta: 'text-slate-300',
                                body: 'text-slate-200',
                                mover: 'border-slate-600 text-slate-300',
                                field: 'bg-slate-950 text-slate-50 border-slate-700 focus:border-rose-400 focus:ring-rose-400',
                                option: 'border-slate-700 bg-slate-900 hover:border-rose-400 hover:bg-slate-800',
                                optionText: 'text-slate-100',
                                later: 'border-slate-600 text-slate-100 hover:bg-slate-800',
                                error: 'border-red-500/30 bg-red-950/40 text-red-200',
                            }
                            : {
                                shell: ['border-rose-200', 'bg-white', 'ring-black/5'],
                                badge: 'bg-rose-600 text-white',
                                title: 'text-slate-900',
                                meta: 'text-slate-500',
                                body: 'text-slate-700',
                                mover: 'border-rose-200 text-rose-500',
                                field: 'border-gray-300 text-slate-900 focus:border-rose-400 focus:ring-rose-400',
                                option: 'border-gray-200 bg-gray-50 hover:border-rose-300 hover:bg-white',
                                optionText: 'text-slate-800',
                                later: 'border-slate-300 text-slate-700 hover:bg-slate-50',
                                error: 'border-red-200 bg-red-50 text-red-700',
                            };
                    }

                    if (type === 'marketing') {
                        return isDark
                            ? {
                                shell: ['border-slate-700/90', 'bg-[linear-gradient(135deg,rgba(15,23,42,0.98),rgba(23,37,84,0.96),rgba(91,33,182,0.94))]'],
                                title: 'text-white/85',
                                body: 'text-white',
                                mover: 'border-white/25 text-white/80',
                                close: 'border-white/30 text-white/80 hover:bg-white/10 hover:text-white',
                            }
                            : {
                                shell: ['border-cyan-200', 'bg-gradient-to-r', 'from-cyan-500', 'via-blue-600', 'to-fuchsia-600'],
                                title: 'text-white/80',
                                body: 'text-white',
                                mover: 'border-white/25 text-white/80',
                                close: 'border-white/30 text-white/80 hover:bg-white/10 hover:text-white',
                            };
                    }

                    if (type === 'reminder') {
                        return isDark
                            ? {
                                shell: ['border-red-500/40', 'bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(35,12,12,0.96))]', 'ring-red-500/20'],
                                title: 'text-slate-50',
                                body: 'text-slate-200',
                                action: 'text-rose-300',
                                close: 'text-slate-400 hover:text-white',
                            }
                            : {
                                shell: ['border-red-300', 'bg-gradient-to-br', 'from-red-50', 'via-white', 'to-amber-50', 'ring-red-200'],
                                title: 'text-gray-900',
                                body: 'text-gray-700',
                                action: 'text-red-700',
                                close: 'text-gray-400 hover:text-gray-600',
                            };
                    }

                    if (type === 'supervisor_status') {
                        return isDark
                            ? {
                                shell: ['border-cyan-500/30', 'bg-[linear-gradient(180deg,rgba(15,23,42,0.98),rgba(10,32,45,0.96))]', 'ring-cyan-500/20'],
                                title: 'text-slate-50',
                                body: 'text-slate-200',
                                action: 'text-cyan-300',
                                close: 'text-slate-400 hover:text-white',
                                badge: 'bg-cyan-500 text-slate-950',
                            }
                            : {
                                shell: ['border-cyan-300', 'bg-gradient-to-br', 'from-cyan-50', 'via-white', 'to-sky-50', 'ring-cyan-200'],
                                title: 'text-gray-900',
                                body: 'text-gray-700',
                                action: 'text-cyan-700',
                                close: 'text-gray-400 hover:text-gray-600',
                                badge: 'bg-cyan-600 text-white',
                            };
                    }

                    return {};
                },
                makeDraggable(element, handle, options = {}) {
                    if (!element || !handle) {
                        return;
                    }

                    const storageKey = options.storageKey || null;
                    const defaultPosition = options.defaultPosition || 'top-right';
                    const persistOnMove = options.persistOnMove !== false;
                    let pointerId = null;
                    let startX = 0;
                    let startY = 0;
                    let originLeft = 0;
                    let originTop = 0;

                    const setPosition = (left, top, persist = true) => {
                        const maxLeft = Math.max(12, window.innerWidth - element.offsetWidth - 12);
                        const maxTop = Math.max(12, window.innerHeight - element.offsetHeight - 12);
                        const nextLeft = Math.min(Math.max(12, left), maxLeft);
                        const nextTop = Math.min(Math.max(12, top), maxTop);
                        element.style.left = `${nextLeft}px`;
                        element.style.top = `${nextTop}px`;

                        if (storageKey && persist) {
                            try {
                                window.sessionStorage.setItem(storageKey, JSON.stringify({ left: nextLeft, top: nextTop }));
                            } catch (error) {
                                console.error('No se pudo guardar la posición del popup.', error);
                            }
                        }
                    };

                    const placeDefault = () => {
                        const width = element.offsetWidth || 360;
                        const height = element.offsetHeight || 220;
                        let left = 24;
                        let top = 24;

                        if (defaultPosition === 'bottom-right') {
                            left = window.innerWidth - width - 24;
                            top = window.innerHeight - height - 24;
                        } else if (defaultPosition === 'center') {
                            left = (window.innerWidth - width) / 2;
                            top = (window.innerHeight - height) / 2;
                        } else if (defaultPosition === 'top-center') {
                            left = (window.innerWidth - width) / 2;
                            top = 16;
                        } else {
                            left = window.innerWidth - width - 24;
                            top = 96;
                        }

                        setPosition(left, top, false);
                    };

                    if (storageKey) {
                        try {
                            const rawValue = window.sessionStorage.getItem(storageKey);
                            if (rawValue) {
                                const parsed = JSON.parse(rawValue);
                                if (typeof parsed.left === 'number' && typeof parsed.top === 'number') {
                                    setPosition(parsed.left, parsed.top, false);
                                } else {
                                    placeDefault();
                                }
                            } else {
                                placeDefault();
                            }
                        } catch (error) {
                            placeDefault();
                        }
                    } else {
                        placeDefault();
                    }

                    const stopDragging = () => {
                        if (pointerId !== null) {
                            try {
                                handle.releasePointerCapture(pointerId);
                            } catch (error) {
                                // ignore
                            }
                        }
                        pointerId = null;
                    };

                    handle.addEventListener('pointerdown', (event) => {
                        if (event.button > 0) {
                            return;
                        }

                        pointerId = event.pointerId;
                        startX = event.clientX;
                        startY = event.clientY;
                        originLeft = parseFloat(element.style.left || '0');
                        originTop = parseFloat(element.style.top || '0');
                        handle.setPointerCapture(pointerId);
                    });

                    handle.addEventListener('pointermove', (event) => {
                        if (pointerId === null || event.pointerId !== pointerId) {
                            return;
                        }

                        setPosition(
                            originLeft + (event.clientX - startX),
                            originTop + (event.clientY - startY),
                            persistOnMove
                        );
                    });

                    handle.addEventListener('pointerup', () => {
                        if (!persistOnMove) {
                            const currentLeft = parseFloat(element.style.left || '0');
                            const currentTop = parseFloat(element.style.top || '0');
                            setPosition(currentLeft, currentTop, true);
                        }

                        stopDragging();
                    });
                    handle.addEventListener('pointercancel', stopDragging);

                    window.addEventListener('resize', () => {
                        const currentLeft = parseFloat(element.style.left || '0');
                        const currentTop = parseFloat(element.style.top || '0');
                        setPosition(currentLeft, currentTop, false);
                    });
                },
            };
        </script>

        @if($showInternalMessagePopups)
            <div id="internalMessageToastContainer" class="pointer-events-none fixed inset-0 z-50"></div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const container = document.getElementById('internalMessageToastContainer');
                    const pulseUrl = @json(route('internal-messages.pulse'));
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const autoDismissMs = 30000;
                    const messageStateStoragePrefix = 'internal-message-state:';
                    const activeMessageIds = new Set();
                    const handledMessageIds = new Set();

                    if (!container || !pulseUrl) {
                        return;
                    }

                    function getMessageStateKey(messageId) {
                        return `${messageStateStoragePrefix}${messageId}`;
                    }

                    function readMessageState(messageId) {
                        try {
                            const rawValue = window.sessionStorage.getItem(getMessageStateKey(messageId));
                            return rawValue ? JSON.parse(rawValue) : null;
                        } catch (error) {
                            return null;
                        }
                    }

                    function writeMessageState(messageId, state) {
                        try {
                            window.sessionStorage.setItem(getMessageStateKey(messageId), JSON.stringify(state));
                        } catch (error) {
                            console.error('No se pudo guardar el estado local del mensaje interno.', error);
                        }
                    }

                    function getStoredMessageStates() {
                        const storedStates = [];

                        try {
                            for (let index = 0; index < window.sessionStorage.length; index += 1) {
                                const key = window.sessionStorage.key(index);

                                if (!key || !key.startsWith(messageStateStoragePrefix)) {
                                    continue;
                                }

                                const rawValue = window.sessionStorage.getItem(key);
                                if (!rawValue) {
                                    continue;
                                }

                                const parsed = JSON.parse(rawValue);
                                const messageId = Number(key.replace(messageStateStoragePrefix, ''));

                                if (!Number.isFinite(messageId)) {
                                    continue;
                                }

                                storedStates.push({
                                    messageId,
                                    ...parsed,
                                });
                            }
                        } catch (error) {
                            console.error('No se pudo leer el estado local de mensajes internos.', error);
                        }

                        return storedStates.sort((a, b) => Number(a.messageId) - Number(b.messageId));
                    }

                    async function markMessageAsRead(message) {
                        try {
                            await window.fetch(message.mark_read_url, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                        } catch (error) {
                            console.error('No se pudo marcar el mensaje como leído.', error);
                        }
                    }

                    function buildMessageToast(message, remainingMs) {
                        const theme = window.crmPopupUtils?.popupTone?.('internal') ?? {};
                        const toast = document.createElement('div');
                        toast.dataset.internalMessageId = String(message.id);
                        toast.className = 'pointer-events-auto absolute w-full max-w-[19.5rem] rounded-[22px] border p-3.5 shadow-2xl ring-1 backdrop-blur';
                        toast.classList.add(...(theme.shell || []));
                        toast.innerHTML = `
                            <div class="flex items-start justify-between gap-2.5 cursor-move select-none" data-message-drag-handle>
                                <div class="min-w-0 flex-1">
                                    <div class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] ${theme.badge}">
                                        Mensaje interno
                                    </div>
                                    <div class="mt-2 text-[15px] font-semibold leading-5 ${theme.title}">${message.title}</div>
                                    <div class="mt-1 text-[12px] ${theme.meta}">De: ${message.sender_name}</div>
                                    <div class="mt-1 text-xs ${theme.meta}">${message.created_at_label}</div>
                                    <div class="mt-2 text-[13px] leading-5 ${theme.body}" data-message-body></div>
                                </div>
                                <div class="flex items-start gap-2">
                                    <span class="mt-0.5 rounded-full border px-2 py-1 text-[9px] font-semibold uppercase tracking-[0.16em] ${theme.mover}">Mover</span>
                                    <button type="button" class="text-[26px] leading-none ${theme.close} transition" data-dismiss-internal-message>&times;</button>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-2">
                                <div class="text-[11px] font-medium ${theme.meta}">Se cerrara automaticamente</div>
                                <div class="rounded-full border px-2 py-0.5 text-[10px] font-semibold ${theme.mover}" data-internal-message-countdown></div>
                            </div>
                            <div class="mt-3 h-1 overflow-hidden rounded-full ${theme.track}">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-cyan-500 via-blue-500 to-fuchsia-500"
                                    data-internal-message-progress
                                ></div>
                            </div>
                        `;

                        toast.querySelector('[data-message-body]').textContent = message.message;
                        const progressBar = toast.querySelector('[data-internal-message-progress]');
                        const countdownNode = toast.querySelector('[data-internal-message-countdown]');
                        const progressPercent = Math.max(0, Math.min(100, (remainingMs / autoDismissMs) * 100));
                        let countdownInterval = null;

                        const updateCountdown = () => {
                            if (!countdownNode) {
                                return;
                            }

                            const secondsLeft = Math.max(0, Math.ceil((dismissAt - Date.now()) / 1000));
                            countdownNode.textContent = `${secondsLeft}s`;
                        };

                        const dismissAt = Date.now() + remainingMs;

                        if (progressBar) {
                            progressBar.style.width = `${progressPercent}%`;
                            progressBar.style.transition = `width ${remainingMs}ms linear`;

                            window.requestAnimationFrame(() => {
                                window.requestAnimationFrame(() => {
                                    progressBar.style.width = '0%';
                                });
                            });
                        }

                        updateCountdown();
                        countdownInterval = window.setInterval(updateCountdown, 1000);

                        const dismiss = async () => {
                            if (countdownInterval) {
                                window.clearInterval(countdownInterval);
                            }
                            activeMessageIds.delete(message.id);
                            handledMessageIds.add(message.id);
                            writeMessageState(message.id, {
                                handled: true,
                                handledAt: Date.now(),
                                message,
                            });
                            toast.remove();
                            await markMessageAsRead(message);
                        };

                        toast.querySelector('[data-dismiss-internal-message]')?.addEventListener('click', dismiss);
                        window.crmPopupUtils?.makeDraggable?.(
                            toast,
                            toast.querySelector('[data-message-drag-handle]'),
                            { storageKey: `internal-message-position:${message.id}`, defaultPosition: 'top-right' }
                        );
                        window.setTimeout(dismiss, remainingMs);

                        return toast;
                    }

                    async function markDisplayed(message) {
                        try {
                            await window.fetch(message.mark_displayed_url, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                        } catch (error) {
                            console.error('No se pudo confirmar el popup del mensaje interno.', error);
                        }
                    }

                    function restoreActiveInternalMessages() {
                        getStoredMessageStates().forEach((storedState) => {
                            const message = storedState?.message;
                            const remainingMs = Number(storedState?.expiresAt || 0) - Date.now();

                            if (!message?.id || handledMessageIds.has(message.id) || activeMessageIds.has(message.id)) {
                                return;
                            }

                            if (storedState?.handled) {
                                handledMessageIds.add(message.id);
                                return;
                            }

                            if (remainingMs <= 0) {
                                handledMessageIds.add(message.id);
                                writeMessageState(message.id, {
                                    handled: true,
                                    handledAt: Date.now(),
                                    message,
                                });
                                markMessageAsRead(message);
                                return;
                            }

                            activeMessageIds.add(message.id);
                            container.appendChild(buildMessageToast(message, remainingMs));
                        });
                    }

                    async function pollInternalMessages() {
                        try {
                            const response = await window.fetch(pulseUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });

                            if (!response.ok) {
                                return;
                            }

                            const payload = await response.json();

                            (payload.messages || []).forEach((message) => {
                                if (activeMessageIds.has(message.id) || handledMessageIds.has(message.id)) {
                                    return;
                                }

                                const existingState = readMessageState(message.id);

                                if (existingState?.handled) {
                                    handledMessageIds.add(message.id);
                                    return;
                                }

                                let remainingMs = autoDismissMs;

                                if (existingState?.expiresAt) {
                                    remainingMs = existingState.expiresAt - Date.now();
                                } else {
                                    writeMessageState(message.id, {
                                        expiresAt: Date.now() + autoDismissMs,
                                        handled: false,
                                        message,
                                    });
                                }

                                if (existingState && !existingState.message) {
                                    writeMessageState(message.id, {
                                        ...existingState,
                                        message,
                                    });
                                }

                                if (remainingMs <= 0) {
                                    handledMessageIds.add(message.id);
                                    writeMessageState(message.id, {
                                        handled: true,
                                        handledAt: Date.now(),
                                        message,
                                    });
                                    markMessageAsRead(message);
                                    return;
                                }

                                activeMessageIds.add(message.id);
                                container.appendChild(buildMessageToast(message, remainingMs));
                                markDisplayed(message);
                            });
                        } catch (error) {
                            console.error('No se pudo refrescar los mensajes internos.', error);
                        }
                    }

                    restoreActiveInternalMessages();
                    pollInternalMessages();
                    window.setInterval(pollInternalMessages, 5000);
                });
            </script>
        @endif

        @if($isExecutive)
            <div id="hrSurveyModalContainer" class="pointer-events-none fixed inset-0 z-[70] hidden"></div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const container = document.getElementById('hrSurveyModalContainer');
                    const pulseUrl = @json(route('rrhh.surveys.pulse'));
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const surveyStateKeyPrefix = 'hr-survey-state:';
                    const activeSurveyIds = new Set();

                    if (!container || !pulseUrl) {
                        return;
                    }

                    function getSurveyStateKey(surveyId) {
                        return `${surveyStateKeyPrefix}${surveyId}`;
                    }

                    function readSurveyState(surveyId) {
                        try {
                            const rawValue = window.sessionStorage.getItem(getSurveyStateKey(surveyId));
                            return rawValue ? JSON.parse(rawValue) : { deferCount: 0, deferredUntil: 0 };
                        } catch (error) {
                            return { deferCount: 0, deferredUntil: 0 };
                        }
                    }

                    function writeSurveyState(surveyId, state) {
                        window.sessionStorage.setItem(getSurveyStateKey(surveyId), JSON.stringify({
                            deferCount: Number(state.deferCount || 0),
                            deferredUntil: Number(state.deferredUntil || 0),
                        }));
                    }

                    function deferSurvey(surveyId) {
                        const currentState = readSurveyState(surveyId);
                        const nextCount = Math.min(Number(currentState.deferCount || 0) + 1, 3);

                        writeSurveyState(surveyId, {
                            deferCount: nextCount,
                            deferredUntil: Date.now() + (2 * 60 * 1000),
                        });
                    }

                    async function markDisplayed(survey) {
                        try {
                            await window.fetch(survey.mark_displayed_url, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });
                        } catch (error) {
                            console.error('No se pudo confirmar la visualización del formulario RRHH.', error);
                        }
                    }

                    async function sendSurveyAnswer(survey, payload) {
                        const response = await window.fetch(survey.answer_url, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(payload),
                        });

                        if (!response.ok) {
                            const data = await response.json().catch(() => ({}));
                            throw new Error(Object.values(data.errors || {}).flat().join(' | ') || 'No se pudo registrar la respuesta.');
                        }
                    }

                    function buildSurveyModal(survey) {
                        const surveyState = readSurveyState(survey.id);
                        const canDefer = Number(surveyState.deferCount || 0) < 2;
                        const theme = window.crmPopupUtils?.popupTone?.('rrhh') ?? {};
                        const popup = document.createElement('div');
                        popup.className = 'pointer-events-auto absolute w-[22rem] max-w-[calc(100vw-1.5rem)] rounded-[24px] border p-4 shadow-2xl ring-1';
                        popup.classList.add(...(theme.shell || []));
                        popup.innerHTML = `
                            <div class="flex items-start gap-3 cursor-move select-none" data-hr-drag-handle>
                                <div class="min-w-0 flex-1">
                                    <div class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] ${theme.badge}">
                                        RRHH
                                    </div>
                                    <div class="mt-2 text-base font-bold ${theme.title}">${survey.title}</div>
                                    <div class="mt-1 text-sm ${theme.meta}">Enviado por ${survey.sender_name}</div>
                                    <div class="mt-2 text-sm leading-6 ${theme.body}" data-hr-prompt></div>
                                </div>
                                <div class="mt-1 rounded-full border ${theme.mover} px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]">
                                    Mover
                                </div>
                            </div>
                            <form class="mt-4 space-y-4" data-hr-form>
                                <div class="max-h-[14rem] space-y-3 overflow-y-auto pr-1" data-hr-options></div>
                                <div data-hr-detail-wrap>
                                    <textarea rows="3" class="block w-full rounded-2xl border text-sm ${theme.field}" data-hr-detail></textarea>
                                </div>
                                <div class="hidden rounded-2xl border px-4 py-3 text-sm ${theme.error}" data-hr-error></div>
                                <div class="flex justify-end gap-3 pt-2">
                                    ${canDefer ? `<button type="button" class="inline-flex items-center justify-center rounded-2xl border px-4 py-2.5 text-sm font-medium transition ${theme.later}" data-hr-later>Más tarde</button>` : ''}
                                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-500">Enviar respuesta</button>
                                </div>
                            </form>
                        `;

                        popup.querySelector('[data-hr-prompt]').textContent = survey.prompt;

                        const optionsWrap = popup.querySelector('[data-hr-options]');
                        const detailWrap = popup.querySelector('[data-hr-detail-wrap]');
                        const detailInput = popup.querySelector('[data-hr-detail]');
                        const errorBox = popup.querySelector('[data-hr-error]');
                        const dragHandle = popup.querySelector('[data-hr-drag-handle]');

                        detailInput.placeholder = survey.detail_placeholder || 'Escribe un breve detalle...';

                        if (survey.response_type === 'text_only') {
                            optionsWrap.classList.add('hidden');
                        } else {
                            (survey.options || []).forEach((option, index) => {
                                const label = document.createElement('label');
                                label.className = `flex cursor-pointer items-start gap-3 rounded-2xl border px-4 py-3 transition ${theme.option}`;
                                label.innerHTML = `
                                    <input type="radio" name="selected_option" value="${option.replace(/"/g, '&quot;')}" class="mt-1 border-gray-300 text-rose-600 focus:ring-rose-500" ${index === 0 ? '' : ''}>
                                    <span class="text-sm font-medium ${theme.optionText}">${option}</span>
                                `;
                                optionsWrap.appendChild(label);
                            });
                        }

                        if (survey.response_type === 'option_only') {
                            detailWrap.classList.add('hidden');
                        }

                        function closeModal() {
                            activeSurveyIds.delete(survey.id);
                            container.classList.add('hidden');
                            container.classList.remove('block');
                            container.innerHTML = '';
                        }

                        popup.querySelector('[data-hr-later]')?.addEventListener('click', () => {
                            deferSurvey(survey.id);
                            closeModal();
                        });

                        popup.querySelector('[data-hr-form]')?.addEventListener('submit', async (event) => {
                            event.preventDefault();
                            errorBox.classList.add('hidden');
                            errorBox.textContent = '';

                            const selectedOption = popup.querySelector('input[name="selected_option"]:checked')?.value || '';
                            const answerDetail = detailInput.value.trim();

                            try {
                                await sendSurveyAnswer(survey, {
                                    selected_option: selectedOption,
                                    answer_detail: answerDetail,
                                });
                                window.sessionStorage.removeItem(getSurveyStateKey(survey.id));
                                closeModal();
                            } catch (error) {
                                errorBox.textContent = error.message || 'No se pudo enviar la respuesta.';
                                errorBox.classList.remove('hidden');
                            }
                        });

                        container.innerHTML = '';
                        container.appendChild(popup);
                        container.classList.remove('hidden');
                        container.classList.add('block');
                        window.crmPopupUtils?.makeDraggable?.(
                            popup,
                            dragHandle,
                            { storageKey: `hr-survey-position:${survey.id}`, defaultPosition: 'bottom-right' }
                        );
                    }

                    async function pollHrSurvey() {
                        if (container.classList.contains('block')) {
                            return;
                        }

                        try {
                            const response = await window.fetch(pulseUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });

                            if (!response.ok) {
                                return;
                            }

                            const payload = await response.json();
                            const survey = payload.survey;

                            if (!survey) {
                                return;
                            }

                            if (activeSurveyIds.has(survey.id)) {
                                return;
                            }

                            const surveyState = readSurveyState(survey.id);

                            if (Number(surveyState.deferredUntil || 0) > Date.now()) {
                                return;
                            }

                            activeSurveyIds.add(survey.id);
                            buildSurveyModal(survey);
                            markDisplayed(survey);
                        } catch (error) {
                            console.error('No se pudo refrescar el formulario de RRHH.', error);
                        }
                    }

                    pollHrSurvey();
                    window.setInterval(pollHrSurvey, 5000);
                });
            </script>
        @endif

        @auth
            <div id="marketingPhraseToastContainer" class="pointer-events-none fixed inset-0 z-[65]"></div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const container = document.getElementById('marketingPhraseToastContainer');
                    const pulseUrl = @json(route('mkt.phrases.pulse'));
                    const marketingPopupStateKey = @json('marketing-active-popup:'.($user?->id ?? 'guest'));
                    let activeToast = null;
                    let pollingHandle = null;

                    if (!container || !pulseUrl) {
                        return;
                    }

                    function getDismissedKey(storageKey) {
                        return `marketing-phrase-dismissed:${storageKey}`;
                    }

                    function hasSeenPhrase(storageKey) {
                        try {
                            return window.localStorage.getItem(storageKey) === '1';
                        } catch (error) {
                            return window.sessionStorage.getItem(storageKey) === '1';
                        }
                    }

                    function markPhraseSeen(storageKey) {
                        try {
                            window.localStorage.setItem(storageKey, '1');
                        } catch (error) {
                            window.sessionStorage.setItem(storageKey, '1');
                        }
                    }

                    function isDismissed(storageKey) {
                        try {
                            return window.sessionStorage.getItem(getDismissedKey(storageKey)) === '1';
                        } catch (error) {
                            return false;
                        }
                    }

                    function markDismissed(storageKey) {
                        try {
                            window.sessionStorage.setItem(getDismissedKey(storageKey), '1');
                        } catch (error) {
                            // ignore
                        }
                    }

                    function readActivePopupState() {
                        try {
                            const rawValue = window.sessionStorage.getItem(marketingPopupStateKey);
                            return rawValue ? JSON.parse(rawValue) : null;
                        } catch (error) {
                            return null;
                        }
                    }

                    function writeActivePopupState(state) {
                        try {
                            window.sessionStorage.setItem(marketingPopupStateKey, JSON.stringify(state));
                        } catch (error) {
                            console.error('No se pudo guardar el estado del popup de MKT.', error);
                        }
                    }

                    function clearActivePopupState() {
                        try {
                            window.sessionStorage.removeItem(marketingPopupStateKey);
                        } catch (error) {
                            // ignore
                        }
                    }

                    function buildMarketingToast(phrase) {
                        const theme = window.crmPopupUtils?.popupTone?.('marketing') ?? {};
                        const toast = document.createElement('div');
                        toast.className = 'pointer-events-auto absolute w-full max-w-xl rounded-[24px] border px-5 py-4 text-white shadow-2xl opacity-0 transition-opacity duration-300';
                        toast.classList.add(...(theme.shell || []));
                        toast.innerHTML = `
                            <div class="flex items-start gap-4">
                                <div class="min-w-0 flex-1 cursor-move select-none" data-marketing-drag-handle>
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <div class="text-[11px] font-semibold uppercase tracking-[0.24em] ${theme.title}">${phrase.title}</div>
                                            <div class="mt-2 text-base font-semibold leading-6 ${theme.body}">${phrase.phrase}</div>
                                        </div>
                                        <span class="mt-1 rounded-full border px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] ${theme.mover}">Mover</span>
                                    </div>
                                </div>
                                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-full border text-xl transition ${theme.close}" data-close-marketing-phrase>&times;</button>
                            </div>
                        `;

                        return toast;
                    }

                    function showMarketingToast(phrase) {
                        const toast = buildMarketingToast(phrase);

                        const dismiss = () => {
                            toast.classList.add('opacity-0');
                            toast.classList.remove('opacity-100');

                            window.setTimeout(() => {
                                if (activeToast === toast) {
                                    activeToast = null;
                                }
                                toast.remove();
                            }, 450);

                            clearActivePopupState();
                        };

                        toast.querySelector('[data-close-marketing-phrase]')?.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            if (phrase?.storage_key) {
                                markPhraseSeen(phrase.storage_key);
                                markDismissed(phrase.storage_key);
                            }
                            dismiss();
                        });
                        if (activeToast) {
                            activeToast.remove();
                        }
                        container.innerHTML = '';
                        container.appendChild(toast);
                        activeToast = toast;
                        window.crmPopupUtils?.makeDraggable?.(
                            toast,
                            toast.querySelector('[data-marketing-drag-handle]'),
                            { defaultPosition: 'top-center', persistOnMove: false }
                        );

                        window.requestAnimationFrame(() => {
                            toast.classList.remove('opacity-0');
                            toast.classList.add('opacity-100');
                        });
                    }

                    const savedPopup = readActivePopupState();
                    if (savedPopup?.phrase) {
                        showMarketingToast(savedPopup.phrase);
                    } else {
                        clearActivePopupState();
                    }

                    async function pollMarketingPhrase() {
                        try {
                            const response = await window.fetch(pulseUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                cache: 'no-store',
                            });

                            if (!response.ok) {
                                return;
                            }

                            const payload = await response.json();
                            const phrase = payload.phrase;

                            if (!phrase) {
                                return;
                            }

                            if (isDismissed(phrase.storage_key)) {
                                return;
                            }

                            if (hasSeenPhrase(phrase.storage_key)) {
                                return;
                            }

                            markPhraseSeen(phrase.storage_key);
                            writeActivePopupState({
                                phrase,
                            });
                            showMarketingToast(phrase);
                        } catch (error) {
                            console.error('No se pudo refrescar la frase motivadora.', error);
                        }
                    }

                    pollMarketingPhrase();
                    pollingHandle = window.setInterval(pollMarketingPhrase, 5000);

                    window.addEventListener('focus', pollMarketingPhrase);
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            pollMarketingPhrase();
                        }
                    });
                });
            </script>
        @endauth

        @if($isExecutive)
            @php
                $executiveActivityModule = match (true) {
                    request()->routeIs('work.*') => 'a_negociar',
                    request()->routeIs('my-work.*') => 'mi_chamba',
                    request()->routeIs('my-sales.*') => 'mis_ventas',
                    request()->routeIs('executive-coverage.*') => 'mi_cobertura',
                    default => null,
                };
            @endphp

            @if($executiveActivityModule)
                @include('partials.executive-activity-tracker', [
                    'moduleName' => $executiveActivityModule,
                    'routeName' => request()->route()?->getName(),
                ])
            @endif

            <div id="callReminderContainer" class="fixed top-4 right-4 z-50 w-full max-w-sm space-y-3 pointer-events-none"></div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const scheduledReminders = @json($executiveScheduledReminders ?? []);
                    const recentToastNotifications = @json($executiveRecentToastNotifications ?? []);
                    const serverNowIso = @json($executiveReminderServerNowIso);
                    const reminderNotificationStoreUrl = @json(route('work.reminder-notifications.store'));
                    const reminderNotificationPulseUrl = @json(route('work.reminder-notifications.pulse'));

                    const reminderContainer = document.getElementById('callReminderContainer');
                    const reminderBadge = document.getElementById('executiveReminderBadge');
                    const reminderDropdownList = document.getElementById('executiveReminderDropdownList');

                    if (!reminderContainer || !serverNowIso) {
                        return;
                    }

                    let serverLoadedAt = new Date(serverNowIso);
                    let perfStart = window.performance.now();
                    const scheduledReminderTimers = new Map();

                    function syncServerClock(serverIso) {
                        if (!serverIso) return;

                        const parsed = new Date(serverIso);
                        if (Number.isNaN(parsed.getTime())) return;

                        serverLoadedAt = parsed;
                        perfStart = window.performance.now();
                    }

                    function getServerAlignedNowMs() {
                        return serverLoadedAt.getTime() + (window.performance.now() - perfStart);
                    }

                    function updateReminderBadge(unreadCount) {
                        if (!reminderBadge) return;

                        const count = Number(unreadCount || 0);
                        reminderBadge.textContent = String(count);
                        reminderBadge.classList.toggle('hidden', count === 0);
                    }

                    function buildDropdownEntry(notification) {
                        const entry = document.createElement('div');
                        entry.setAttribute('data-reminder-notification-id', notification.id);
                        entry.className = `px-4 py-3 border-b border-gray-100 ${notification.read_at ? 'bg-white' : 'bg-red-50'}`;

                        const wrapper = document.createElement('div');
                        wrapper.className = 'flex items-start justify-between gap-3';

                        const content = document.createElement('a');
                        content.href = notification.open_url;
                        content.className = 'min-w-0 flex-1 rounded-lg transition hover:bg-white/70';

                        const titleNode = document.createElement('div');
                        titleNode.className = 'text-sm font-semibold text-gray-900';
                        titleNode.textContent = notification.title;

                        const messageNode = document.createElement('div');
                        messageNode.className = 'mt-1 text-sm text-gray-700';
                        messageNode.textContent = notification.message;

                        const stageNode = document.createElement('div');
                        stageNode.className = 'mt-2 inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-red-700';
                        stageNode.textContent = notification.stage_label;

                        const timeNode = document.createElement('div');
                        timeNode.className = 'mt-2 text-xs text-gray-500';
                        timeNode.textContent = notification.notified_at_label;

                        content.appendChild(titleNode);
                        content.appendChild(messageNode);
                        content.appendChild(stageNode);
                        content.appendChild(timeNode);

                        const markReadForm = document.createElement('form');
                        markReadForm.method = 'POST';
                        markReadForm.action = notification.mark_read_url;

                        const tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        tokenInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                        const button = document.createElement('button');
                        button.type = 'submit';
                        button.className = 'text-xs font-medium text-indigo-600 hover:text-indigo-700 whitespace-nowrap';
                        button.textContent = 'Marcar leida';

                        markReadForm.appendChild(tokenInput);
                        markReadForm.appendChild(button);

                        wrapper.appendChild(content);
                        if (!notification.read_at) {
                            wrapper.appendChild(markReadForm);
                        }
                        entry.appendChild(wrapper);

                        return entry;
                    }

                    function prependReminderToDropdown(notification) {
                        if (!reminderDropdownList || !notification?.id) return;

                        if (reminderDropdownList.querySelector(`[data-reminder-notification-id="${notification.id}"]`)) {
                            return;
                        }

                        reminderDropdownList.querySelector('[data-empty-reminders]')?.remove();
                        reminderDropdownList.prepend(buildDropdownEntry(notification));
                    }

                    function replaceReminderDropdown(notifications) {
                        if (!reminderDropdownList) return;

                        reminderDropdownList.innerHTML = '';

                        if (!Array.isArray(notifications) || notifications.length === 0) {
                            const emptyState = document.createElement('div');
                            emptyState.setAttribute('data-empty-reminders', '');
                            emptyState.className = 'px-4 py-8 text-sm text-center text-gray-500';
                            emptyState.textContent = 'Aun no tienes recordatorios guardados.';
                            reminderDropdownList.appendChild(emptyState);
                            return;
                        }

                        notifications.forEach((notification) => {
                            reminderDropdownList.appendChild(buildDropdownEntry(notification));
                        });
                    }

                    async function syncReminderNotification(reminder, stage, toast) {
                        if (stage !== 't_minus_5') {
                            return;
                        }

                        try {
                            const response = await window.fetch(reminderNotificationStoreUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                },
                                body: JSON.stringify({
                                    interaction_id: reminder.interaction_id,
                                    reminder_stage: stage,
                                }),
                            });

                            if (!response.ok) {
                                return;
                            }

                            const payload = await response.json();
                            updateReminderBadge(payload.unread_count);
                            prependReminderToDropdown(payload.notification);
                            toast.querySelector('[data-open-reminder]')?.setAttribute('href', payload.notification.open_url);
                        } catch (error) {
                            console.error('No se pudo sincronizar el recordatorio.', error);
                        }
                    }

                    function buildToast(title, message, stageLabel, openUrl = '#') {
                        const toast = document.createElement('div');
                        const theme = window.crmPopupUtils?.popupTone?.('reminder') ?? {};
                        toast.className = 'pointer-events-auto w-full rounded-2xl border shadow-2xl p-5 ring-1';
                        toast.classList.add(...(theme.shell || []));
                        toast.innerHTML = `<div class="flex items-start justify-between gap-4"><a href="${openUrl}" class="block flex-1 rounded-xl pr-2" data-open-reminder><div class="inline-flex items-center rounded-full bg-red-600 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-white">${stageLabel}</div><div class="mt-3 text-lg font-semibold ${theme.title}">${title}</div><div class="mt-2 text-base ${theme.body} leading-6" data-reminder-message></div><div class="mt-3 text-sm font-semibold ${theme.action}">Da clic aqui para abrir el detalle en Mi chamba</div></a><button type="button" class="${theme.close} text-xl leading-none" data-dismiss-reminder>&times;</button></div>`;

                        const messageNode = toast.querySelector('[data-reminder-message]');
                        if (messageNode) {
                            messageNode.textContent = message;
                        }

                        const removeToast = () => {
                            toast.remove();
                        };

                        toast.querySelector('[data-dismiss-reminder]')?.addEventListener('click', removeToast);
                        window.setTimeout(removeToast, 45000);

                        return toast;
                    }

                    function showReminder(reminder, stage) {
                        const storageKey = `call-reminder-${reminder.interaction_id}-${reminder.next_contact_at}-${stage}`;
                        if (window.localStorage.getItem(storageKey)) {
                            return;
                        }

                        window.localStorage.setItem(storageKey, '1');

                        const stageLabel = stage === 't_minus_4'
                            ? 'Faltan menos de 4 minutos'
                            : 'Faltan menos de 5 minutos';

                        const toast = buildToast(
                            'Recordatorio de llamada',
                            `Debes volver a llamar a ${reminder.lead_label} a las ${reminder.next_contact_at_label}.`,
                            stageLabel,
                            reminder.open_url ?? '#'
                        );

                        reminderContainer.appendChild(toast);
                        syncReminderNotification(reminder, stage, toast);
                    }

                    function replayRecentNotification(notification) {
                        const replayKey = `replayed-reminder-notification-${notification.id}`;
                        if (window.sessionStorage.getItem(replayKey)) {
                            return;
                        }

                        window.sessionStorage.setItem(replayKey, '1');
                        if (notification.storage_key) {
                            window.localStorage.setItem(notification.storage_key, '1');
                        }

                        const toast = buildToast(
                            notification.title,
                            notification.message,
                            notification.stage_label,
                            notification.open_url
                        );

                        reminderContainer.appendChild(toast);
                    }

                    function scheduleReminder(reminder) {
                        if (!reminder?.next_contact_at) return;

                        const scheduledAt = new Date(reminder.next_contact_at);
                        if (Number.isNaN(scheduledAt.getTime())) return;

                        [
                            { stage: 't_minus_5', offsetMs: 5 * 60 * 1000 },
                            { stage: 't_minus_4', offsetMs: 4 * 60 * 1000 },
                        ].forEach(({ stage, offsetMs }) => {
                            const timerKey = `${reminder.interaction_id}-${reminder.next_contact_at}-${stage}`;
                            const reminderAt = new Date(scheduledAt.getTime() - offsetMs);
                            const delay = reminderAt.getTime() - getServerAlignedNowMs();

                            if (delay <= 0 && scheduledAt.getTime() > getServerAlignedNowMs()) {
                                showReminder(reminder, stage);
                                return;
                            }

                            if (delay > 0 && !scheduledReminderTimers.has(timerKey)) {
                                const timeoutId = window.setTimeout(() => {
                                    scheduledReminderTimers.delete(timerKey);
                                    showReminder(reminder, stage);
                                }, delay);

                                scheduledReminderTimers.set(timerKey, timeoutId);
                            }
                        });
                    }

                    async function pollReminderState() {
                        try {
                            const response = await window.fetch(reminderNotificationPulseUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });

                            if (!response.ok) {
                                return;
                            }

                            const payload = await response.json();
                            syncServerClock(payload.server_now_iso);
                            updateReminderBadge(payload.unread_count);
                            replaceReminderDropdown(payload.notifications);

                            (payload.recent_toast_notifications || []).forEach(replayRecentNotification);
                            (payload.scheduled_reminders || []).forEach(scheduleReminder);
                        } catch (error) {
                            console.error('No se pudo refrescar el estado de recordatorios.', error);
                        }
                    }

                    recentToastNotifications.forEach(replayRecentNotification);
                    scheduledReminders.forEach(scheduleReminder);
                    window.setInterval(pollReminderState, 5000);
                });
            </script>
        @endif

        @if($isSupervisor)
            <div id="supervisorStatusNotificationContainer" class="fixed top-4 right-4 z-50 w-full max-w-sm space-y-3 pointer-events-none"></div>

            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const recentToastNotifications = @json($supervisorRecentStatusToastNotifications ?? []);
                    const pulseUrl = @json(route('supervisor.status-notifications.pulse'));
                    const supervisorNotificationPollMs = 5000;
                    const notificationContainer = document.getElementById('supervisorStatusNotificationContainer');
                    const desktopBadge = document.getElementById('supervisorStatusNotificationBadge');
                    const mobileBadge = document.getElementById('supervisorStatusNotificationBadgeMobile');
                    const dropdownList = document.getElementById('supervisorStatusNotificationDropdownList');

                    if (!notificationContainer) {
                        return;
                    }

                    let pollInFlight = false;

                    function updateBadge(unreadCount) {
                        const count = Number(unreadCount || 0);

                        [desktopBadge, mobileBadge].forEach((badge) => {
                            if (!badge) return;

                            badge.textContent = String(count);
                            badge.classList.toggle('hidden', count === 0);
                        });
                    }

                    function buildDropdownEntry(notification) {
                        const entry = document.createElement('div');
                        entry.setAttribute('data-supervisor-status-notification-id', notification.id);
                        entry.className = `rounded-2xl border border-slate-200 bg-white p-3 ${notification.read_at ? '' : 'ring-1 ring-cyan-100'}`;

                        const link = document.createElement('a');
                        link.href = notification.open_url;
                        link.className = 'block';

                        const wrapper = document.createElement('div');
                        wrapper.className = 'flex items-start justify-between gap-3';

                        const content = document.createElement('div');
                        content.className = 'min-w-0';

                        const titleNode = document.createElement('div');
                        titleNode.className = 'text-sm font-semibold text-slate-900';
                        titleNode.textContent = notification.title;

                        const messageNode = document.createElement('div');
                        messageNode.className = 'mt-1 text-xs text-slate-600';
                        messageNode.textContent = notification.message;

                        const statusNode = document.createElement('span');
                        statusNode.className = 'inline-flex shrink-0 items-center rounded-full bg-cyan-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-cyan-700';
                        statusNode.textContent = notification.status_label;

                        const timeNode = document.createElement('div');
                        timeNode.className = 'mt-2 text-[11px] text-slate-500';
                        timeNode.textContent = notification.notified_at_label;

                        content.appendChild(titleNode);
                        content.appendChild(messageNode);
                        wrapper.appendChild(content);
                        wrapper.appendChild(statusNode);
                        link.appendChild(wrapper);
                        link.appendChild(timeNode);
                        entry.appendChild(link);

                        return entry;
                    }

                    function replaceDropdown(notifications) {
                        if (!dropdownList) return;

                        dropdownList.innerHTML = '';

                        if (!Array.isArray(notifications) || notifications.length === 0) {
                            const emptyState = document.createElement('div');
                            emptyState.setAttribute('data-empty-supervisor-status-notifications', '');
                            emptyState.className = 'rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-5 text-center text-sm text-slate-500';
                            emptyState.textContent = 'Aun no tienes avisos de Mesa de Control.';
                            dropdownList.appendChild(emptyState);
                            return;
                        }

                        notifications.forEach((notification) => {
                            dropdownList.appendChild(buildDropdownEntry(notification));
                        });
                    }

                    function buildToast(title, message, statusLabel, openUrl = '#') {
                        const toast = document.createElement('div');
                        const theme = window.crmPopupUtils?.popupTone?.('supervisor_status') ?? {};
                        toast.className = 'pointer-events-auto w-full rounded-2xl border shadow-2xl p-5 ring-1';
                        toast.classList.add(...(theme.shell || []));
                        toast.innerHTML = `<div class="flex items-start justify-between gap-4"><a href="${openUrl}" class="block flex-1 rounded-xl pr-2"><div class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] ${theme.badge}">${statusLabel}</div><div class="mt-3 text-lg font-semibold ${theme.title}">${title}</div><div class="mt-2 text-base ${theme.body} leading-6" data-supervisor-status-message></div><div class="mt-3 text-sm font-semibold ${theme.action}">Da clic aqui para abrir el acuerdo en supervisor</div></a><button type="button" class="${theme.close} text-xl leading-none" data-dismiss-supervisor-status>&times;</button></div>`;

                        toast.querySelector('[data-supervisor-status-message]').textContent = message;

                        const removeToast = () => {
                            toast.remove();
                        };

                        toast.querySelector('[data-dismiss-supervisor-status]')?.addEventListener('click', removeToast);
                        window.setTimeout(removeToast, 45000);

                        return toast;
                    }

                    function replayNotification(notification) {
                        const replayKey = `replayed-supervisor-status-notification-${notification.id}`;
                        if (window.sessionStorage.getItem(replayKey)) {
                            return;
                        }

                        window.sessionStorage.setItem(replayKey, '1');
                        if (notification.storage_key) {
                            window.localStorage.setItem(notification.storage_key, '1');
                        }

                        notificationContainer.appendChild(
                            buildToast(
                                notification.title,
                                notification.message,
                                notification.status_label,
                                notification.open_url
                            )
                        );
                    }

                    async function pollSupervisorNotifications() {
                        if (pollInFlight || document.hidden) {
                            return;
                        }

                        pollInFlight = true;

                        try {
                            const response = await window.fetch(pulseUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                            });

                            if (!response.ok) {
                                return;
                            }

                            const payload = await response.json();
                            updateBadge(payload.unread_count);
                            replaceDropdown(payload.notifications);
                            (payload.recent_toast_notifications || []).forEach(replayNotification);
                        } catch (error) {
                            console.error('No se pudieron refrescar las notificaciones del supervisor.', error);
                        } finally {
                            pollInFlight = false;
                        }
                    }

                    recentToastNotifications.forEach(replayNotification);
                    pollSupervisorNotifications();
                    window.setInterval(pollSupervisorNotifications, supervisorNotificationPollMs);
                    window.addEventListener('focus', pollSupervisorNotifications);
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            pollSupervisorNotifications();
                        }
                    });
                });
            </script>
        @endif
    </body>
</html>
