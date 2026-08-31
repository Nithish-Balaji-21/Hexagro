@php
    $isVikas = auth()->user()->configKey() === 'vikas';
    $isAdmin = auth()->user()->isAdmin();

    $transactionItems = [
        ['id' => 'debit', 'label' => 'Debit', 'route' => 'debit', 'icon' => 'down'],
        ['id' => 'credit', 'label' => 'Credit', 'route' => 'credit', 'icon' => 'up'],
        ['id' => 'transfers', 'label' => 'Transfers', 'route' => 'transfers', 'icon' => 'transfer'],
    ];

    if (!$isVikas) {
        $transactionItems[] = ['id' => 'banking', 'label' => 'Banking', 'route' => 'banking', 'icon' => 'bank'];
    }

    $financeItems = [
        ['id' => 'entityLedgers', 'label' => 'Ledger Book', 'route' => 'ledger-book', 'icon' => 'history'],
    ];

    $navSections = [
        'Overview' => [
            ['id' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid'],
        ],
        'Transactions' => $transactionItems,
        'Reports' => [
            ['id' => 'monthlySpend', 'label' => 'Monthly Spend', 'route' => 'monthly-spend', 'icon' => 'calendar'],
            ['id' => 'settlement', 'label' => 'Summary & Settlement', 'route' => 'settlement', 'icon' => 'settlement'],
            ['id' => 'purchases', 'label' => 'Purchases', 'route' => 'purchases', 'icon' => 'down'],
            ['id' => 'sales', 'label' => 'Sales', 'route' => 'sales', 'icon' => 'trend'],
        ],
        'Finance' => $financeItems,
    ];

    $currentPage = $currentPage ?? 'dashboard';
    $pageTitle = $pageTitle ?? 'Dashboard';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? $pageTitle.' — '.config('app.name', 'Hexagro') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    class="min-h-screen antialiased"
    x-data="{
        sidebarCollapsed: false,
        mobileOpen: false,
        profileOpen: false,
    }"
    @keydown.escape.window="mobileOpen = false; profileOpen = false"
>
    <div
        class="drawer-overlay"
        :class="{ 'show': mobileOpen }"
        @click="mobileOpen = false"
    ></div>

    <div class="app-shell">
        <aside
            class="sidebar"
            :class="{ 'collapsed': sidebarCollapsed, 'mobile-open': mobileOpen }"
            id="sidebar"
        >
            <div class="sb-head">
                <div class="mark">HX</div>
                <div class="brand">Hexagro</div>
            </div>

            <nav class="sb-nav">
                @foreach ($navSections as $section => $items)
                    <div class="sb-section-label">{{ $section }}</div>
                    @foreach ($items as $item)
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            class="nav-item {{ $currentPage === $item['id'] ? 'active' : '' }}"
                        >
                            <x-hex.icon :name="$item['icon']" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="sb-foot">
                <button
                    type="button"
                    class="sb-collapse-btn"
                    @click="sidebarCollapsed = !sidebarCollapsed"
                >
                    <x-hex.icon name="chev-left" class="transition-transform" ::class="{ 'rotate-180': sidebarCollapsed }" />
                    <span class="sb-foot-text">Collapse</span>
                </button>
            </div>
        </aside>

        <div class="main-col" :class="{ 'collapsed': sidebarCollapsed }" id="mainCol">
            <header class="topbar">
                <div class="topbar-left">
                    <button
                        type="button"
                        class="hamburger"
                        @click="mobileOpen = !mobileOpen"
                        aria-label="Open menu"
                    >
                        <x-hex.icon name="menu" />
                    </button>
                    <div class="crumb">
                        <span>Hexagro</span>
                        <x-hex.icon name="chev-right" class="opacity-50" />
                        <b>{{ $pageTitle }}</b>
                    </div>
                </div>

                <div class="topbar-right">
                    <livewire:layout.unit-switcher />

                    <div class="relative">
                        <button
                            type="button"
                            class="profile-btn"
                            @click.stop="profileOpen = !profileOpen"
                        >
                            <div class="profile-avatar">{{ auth()->user()->initials }}</div>
                            <div class="profile-meta">
                                <b>{{ auth()->user()->name }}</b>
                                <span>{{ auth()->user()->role->value === 'ADMIN' ? 'Admin' : 'Viewer' }}</span>
                            </div>
                            <x-hex.icon name="chev-down" />
                        </button>

                        <div
                            x-show="profileOpen"
                            x-cloak
                            @click.outside="profileOpen = false"
                            class="profile-menu hex-card"
                        >
                            <div class="profile-menu-head">
                                <div class="font-semibold text-[13px]">{{ auth()->user()->name }}</div>
                                <div class="text-[11px] text-[var(--text-3)]">
                                    {{ auth()->user()->role->value === 'ADMIN' ? 'Admin' : 'Viewer' }} access
                                </div>
                            </div>
                            @if (auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('switch-user') }}">
                                    @csrf
                                    <button type="submit" class="profile-menu-item w-full text-left">
                                        <x-hex.icon name="grid" />
                                        <span>Switch user</span>
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="profile-menu-item profile-menu-logout">
                                    <x-hex.icon name="logout" />
                                    <span>Log out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content">
                {{ $slot }}
            </div>
        </div>
    </div>

    <div
        id="toast-container"
        class="toast-container"
        x-data
        x-on:hexagro-toast.window="
            $el.querySelector('.toast-live')?.remove();
            const t = document.createElement('div');
            t.className = 'toast toast-live';
            t.textContent = $event.detail.message;
            $el.appendChild(t);
            setTimeout(() => t.remove(), 3200);
        "
    ></div>

    <livewire:import.excel-import-modal />

    @livewireScripts
</body>
</html>
