<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'AeroGuard Dash') | Mission Control</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />

    {{-- Vite: map CSS (Tailwind + Leaflet CSS), Echo/WebSocket client, shared map JS, layout JS --}}
    @vite(['resources/css/map.css', 'resources/js/bootstrap.js', 'resources/js/map.js', 'resources/js/map-layout.js'])


    <style>
        /* Prevent any scroll; map fills viewport */
        html, body { height: 100%; overflow: hidden; min-height: max(884px, 100dvh); }

        /* Material Symbols default weight */
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>

    @stack('head')
</head>
<body class="bg-[#10131a] text-[#e1e2ec] font-sans overflow-hidden">

    {{-- ── Top Navigation Bar ──────────────────────────────────────── --}}
    <header id="topbar"
        class="fixed top-0 left-0 right-0 h-16 z-50
               flex items-center justify-between px-4
               bg-[#10131a]/80 backdrop-blur-xl
               border-b border-[#424754]/30">

        <div class="flex items-center gap-4">
            {{-- Sidebar toggle --}}
            <button id="sidebar-toggle"
                class="min-w-[48px] min-h-[48px] flex items-center justify-center
                       text-[#adc6ff] hover:bg-[#32353c]/50
                       transition-colors rounded-lg"
                aria-label="Toggle sidebar">
                <span class="material-symbols-outlined">menu</span>
            </button>

            <h1 class="text-[32px] leading-[40px] tracking-[-0.02em] font-bold text-[#adc6ff]">
                AeroGuard Dash
            </h1>
        </div>

        <div class="flex items-center gap-2">
            {{-- Zoom to fit --}}
            <button id="btn-fit-bounds"
                class="min-w-[48px] min-h-[48px] flex items-center justify-center
                       text-[#c2c6d6] hover:bg-[#32353c]/50 transition-colors rounded-lg"
                title="Zoom to fit all drones">
                <span class="material-symbols-outlined">zoom_out_map</span>
            </button>

            {{-- Layer toggle --}}
            <button
                class="min-w-[48px] min-h-[48px] flex items-center justify-center
                       text-[#c2c6d6] hover:bg-[#32353c]/50 transition-colors rounded-lg"
                title="Toggle layers">
                <span class="material-symbols-outlined">layers</span>
            </button>

            {{-- Notifications --}}
            <button
                class="relative min-w-[48px] min-h-[48px] flex items-center justify-center
                       text-[#c2c6d6] hover:bg-[#32353c]/50 transition-colors rounded-lg"
                title="Notifications">
                <span class="material-symbols-outlined">notifications</span>
                @yield('notification-dot')
            </button>

            {{-- Avatar --}}
            <div class="ml-2 w-10 h-10 rounded-full bg-[#3e495d] border border-[#424754]/30
                        flex items-center justify-center">
                <span class="material-symbols-outlined text-[#aeb9d0]">account_circle</span>
            </div>
        </div>
    </header>

    {{-- ── Side Navigation Bar ─────────────────────────────────────── --}}
    <aside id="sidebar"
        class="fixed left-0 top-0 h-full w-[320px] z-40
               bg-[#10131a] border-r border-[#424754]/30
               pt-20 pb-6 flex flex-col
               sidebar-transition">

        <div class="px-6 mb-8">
            <h2 class="text-[20px] leading-[28px] font-semibold text-[#e1e2ec]">Mission Control</h2>
            <p class="text-[11px] leading-[16px] tracking-[0.05em] font-bold text-[#c2c6d6] uppercase mt-1">
                @php
                    $activeCount = 0;
                    if (isset($drones)) {
                        $activeCount = $drones->filter(fn($d) => (string)($d->status?->value ?? $d->status) === 'active')->count();
                    } elseif (isset($drone)) {
                        $activeCount = (string) ($drone->status?->value ?? $drone->status) === 'active' ? 1 : 0;
                    }
                @endphp
                Active Ops: {{ $activeCount }}
            </p>
        </div>

        {{-- Nav links --}}
        <nav class="px-3 space-y-1">
            <a href="{{ route('map.index') }}"
               class="flex items-center gap-4 px-4 py-4 rounded-lg transition-all cursor-pointer
                      {{ request()->routeIs('map.index') && !request()->route('drone')
                         ? 'text-[#adc6ff] font-bold border-l-4 border-[#adc6ff] bg-[#4d8eff]/10 rounded-r-lg'
                         : 'text-[#c2c6d6] hover:bg-[#272a31]' }}">
                <span class="material-symbols-outlined"
                      @if(request()->routeIs('map.index')) style="font-variation-settings: 'FILL' 1;" @endif>
                    rocket_launch
                </span>
                <span class="text-[11px] leading-[16px] tracking-[0.05em] font-bold uppercase">Fleet</span>
            </a>

            <div class="text-[#c2c6d6] hover:bg-[#272a31] px-4 py-4 rounded-lg flex items-center gap-4 transition-all cursor-pointer">
                <span class="material-symbols-outlined">monitoring</span>
                <span class="text-[11px] leading-[16px] tracking-[0.05em] font-bold uppercase">Telemetry</span>
            </div>

            <div class="text-[#c2c6d6] hover:bg-[#272a31] px-4 py-4 rounded-lg flex items-center gap-4 transition-all cursor-pointer">
                <span class="material-symbols-outlined">map</span>
                <span class="text-[11px] leading-[16px] tracking-[0.05em] font-bold uppercase">Flight Paths</span>
            </div>

            <a href="{{ url('/admin') }}"
               class="flex items-center gap-4 px-4 py-4 rounded-lg transition-all cursor-pointer
                      text-[#c2c6d6] hover:bg-[#272a31]">
                <span class="material-symbols-outlined">settings</span>
                <span class="text-[11px] leading-[16px] tracking-[0.05em] font-bold uppercase">Settings</span>
            </a>

            <div class="pt-6 pb-2 px-3">
                <span class="text-[10px] font-bold text-[#8c909f] uppercase tracking-widest">
                    Live Fleet Status
                </span>
            </div>
        </nav>

        {{-- Drone list — injected by child view --}}
        <div class="flex-1 overflow-y-auto px-3 space-y-2 pb-2">
            @yield('sidebar-drone-list')
        </div>

        {{-- Sidebar footer --}}
        <div class="px-6 pt-6 border-t border-[#424754]/30">
            <button class="w-full bg-[#adc6ff] text-[#002e6a] py-4 rounded-xl
                           font-bold text-[11px] tracking-widest uppercase
                           shadow-lg shadow-[#adc6ff]/20 active:scale-[0.98] transition-transform">
                Launch Drone
            </button>
            <div class="mt-6 flex justify-around">
                <button class="flex flex-col items-center gap-1 text-[#c2c6d6] hover:text-[#adc6ff] transition-colors">
                    <span class="material-symbols-outlined">help</span>
                    <span class="text-[10px] font-bold uppercase">Help</span>
                </button>
                <button class="flex flex-col items-center gap-1 text-[#c2c6d6] hover:text-[#adc6ff] transition-colors">
                    <span class="material-symbols-outlined">history</span>
                    <span class="text-[10px] font-bold uppercase">Logs</span>
                </button>
            </div>
        </div>
    </aside>

    {{-- ── Main content area ───────────────────────────────────────── --}}
    <main id="main-content"
        class="fixed inset-0 pt-16 sidebar-transition"
        style="margin-left: 320px;">
        @yield('map-area')
    </main>

    {{-- ── WebSocket offline banner ─────────────────────────────────── --}}
    <div id="ws-offline-banner"
        class="hidden fixed top-[72px] left-1/2 -translate-x-1/2 z-[70]
               bg-[#93000a] text-[#ffdad6]
               text-xs font-semibold px-4 py-1.5 rounded-lg
               flex items-center gap-2 pointer-events-none">
        <span class="material-symbols-outlined text-sm">wifi_off</span>
        Live updates offline
    </div>

    {{-- Bootstrap data for map.js (server-rendered JSON, must stay inline) --}}
    @stack('map-data')

    {{-- Page-specific JS files --}}
    @stack('scripts')
</body>
</html>
