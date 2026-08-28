<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Hexagro') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen antialiased">
    <main class="mx-auto flex min-h-screen max-w-3xl flex-col justify-center px-6 py-12">
        <div class="hex-card p-8">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[var(--primary)] font-[family-name:var(--font-display)] text-sm font-extrabold text-white">
                    HX
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Hexagro</h1>
                    <p class="text-sm text-[var(--text-2)]">Shareholding &amp; Finance</p>
                </div>
            </div>

            <p class="mb-6 text-[var(--text-2)]">
                Phase 0 environment setup is complete. Laravel, Livewire, MySQL config, Tailwind design tokens, and Chart.js are ready.
            </p>

            <div class="mb-6 grid gap-3 sm:grid-cols-3">
                <div class="hex-kpi">
                    <div class="hex-kpi-label">Stack</div>
                    <div class="hex-kpi-value text-base">Laravel 13</div>
                </div>
                <div class="hex-kpi">
                    <div class="hex-kpi-label">UI</div>
                    <div class="hex-kpi-value text-base">Livewire 3</div>
                </div>
                <div class="hex-kpi">
                    <div class="hex-kpi-label">Database</div>
                    <div class="hex-kpi-value text-base">MySQL 8</div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <span class="rounded-full bg-[var(--primary-tint)] px-3 py-1 text-xs font-semibold text-[var(--primary-dark)]">Phase 0 ✓</span>
                <span class="rounded-full bg-[var(--amber-tint)] px-3 py-1 text-xs font-semibold text-[var(--amber)]">Next: Schema migrations</span>
            </div>
        </div>
    </main>

    @livewireScripts
</body>
</html>
