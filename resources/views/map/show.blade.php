@extends('layouts.map')

@section('title', $drone->name)

{{-- Notification dot for active alerts --}}
@section('notification-dot')
    @if($activeAlerts->count())
        <span class="absolute top-2 right-2 w-2 h-2 bg-[#ffb4ab] rounded-full"></span>
    @endif
@endsection

{{-- ── Sidebar: drone list scoped to this drone ───────────────────── --}}
@section('sidebar-drone-list')
    @php
        $status  = (string) ($drone->status?->value ?? $drone->status);
        $battery = (int) $drone->battery_level;
        $segments = 5;
        $filled   = (int) round($battery / 100 * $segments);

        $statusLabel = match($status) {
            'active'      => 'In Flight',
            'emergency'   => 'Critical',
            'maintenance' => 'Maintenance',
            default       => 'Offline',
        };

        $dotClass = match($status) {
            'active'      => 'bg-[#22c55e] animate-pulse',
            'emergency'   => 'bg-[#ef4444]',
            'maintenance' => 'bg-[#eab308]',
            default       => 'bg-[#6b7280]',
        };

        $badgeClass = match($status) {
            'active'      => 'text-[#adc6ff] bg-[#adc6ff]/10 border-[#adc6ff]/20',
            'emergency'   => 'text-[#ffb4ab] bg-[#ffb4ab]/10 border-[#ffb4ab]/20',
            'maintenance' => 'text-[#eab308] bg-[#eab308]/10 border-[#eab308]/20',
            default       => 'text-[#6b7280] bg-[#6b7280]/10 border-[#6b7280]/20',
        };

        $batteryFillClass = match(true) {
            $battery >= 50 => 'bg-[#22c55e]',
            $battery >= 25 => 'bg-[#eab308]',
            default        => 'bg-[#ef4444]',
        };

        $batteryTextClass = $battery < 25 ? 'text-[#ffb4ab]' : 'text-[#e1e2ec]';
    @endphp

    {{-- Selected drone card --}}
    <div class="mx-1 p-4 bg-[#4d8eff]/8 border border-[#adc6ff]/30 rounded-xl flex flex-col gap-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ $dotClass }}"></span>
                <span class="font-mono text-[13px] font-medium text-[#e1e2ec]">{{ $drone->name }}</span>
            </div>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded border {{ $badgeClass }}">
                {{ strtoupper($statusLabel) }}
            </span>
        </div>

        <div class="flex items-center gap-4">
            <div class="flex-1">
                <div class="flex justify-between text-[10px] mb-1">
                    <span class="text-[#c2c6d6] uppercase">Battery</span>
                    <span class="font-mono {{ $batteryTextClass }}">{{ $battery }}%</span>
                </div>
                <div class="flex gap-0.5 h-1.5 w-full">
                    @for($i = 0; $i < $segments; $i++)
                        <div class="flex-1
                            {{ $i === 0 ? 'rounded-l-full' : '' }}
                            {{ $i === $segments - 1 ? 'rounded-r-full' : '' }}
                            {{ $i < $filled ? $batteryFillClass : 'bg-[#424754]/30' }}">
                        </div>
                    @endfor
                </div>
            </div>
            <div class="text-right">
                <div class="text-[10px] text-[#c2c6d6] uppercase">Alt</div>
                <div class="font-mono text-[13px] text-[#e1e2ec]">
                    {{ $drone->current_altitude ? round($drone->current_altitude).'m' : '—' }}
                </div>
            </div>
        </div>

        {{-- Follow mode toggle --}}
        <button id="btn-follow"
            class="w-full flex items-center justify-center gap-2 py-2 rounded-lg
                   border border-[#adc6ff]/30 text-[#adc6ff] text-[11px] font-bold uppercase tracking-widest
                   hover:bg-[#adc6ff]/10 transition-colors">
            <span class="material-symbols-outlined text-sm">near_me</span>
            Follow Drone
        </button>
    </div>

    {{-- Back to fleet --}}
    <a href="{{ route('map.index') }}"
       class="mx-1 flex items-center gap-2 px-4 py-3 rounded-xl
              text-[#c2c6d6] hover:bg-[#272a31] transition-colors">
        <span class="material-symbols-outlined text-sm">arrow_back</span>
        <span class="text-[11px] font-bold uppercase tracking-widest">All Drones</span>
    </a>

    {{-- Active alerts --}}
    @if($activeAlerts->count())
        <div class="mx-1 mt-2">
            <p class="text-[10px] font-bold text-[#8c909f] uppercase tracking-widest px-3 mb-2">Active Alerts</p>
            @foreach($activeAlerts->take(3) as $alert)
                <div class="p-3 bg-[#93000a]/30 border border-[#ffb4ab]/20 rounded-xl mb-1.5">
                    <div class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[#ffb4ab] text-sm mt-0.5">warning</span>
                        <div>
                            <p class="text-[11px] font-bold text-[#ffb4ab] uppercase">
                                {{ $alert->type?->value ?? $alert->type }}
                            </p>
                            <p class="text-[10px] text-[#c2c6d6] mt-0.5">
                                {{ $alert->message ?? '—' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection

{{-- ── Map area ────────────────────────────────────────────────────── --}}
@section('map-area')
    <div class="relative w-full h-full overflow-hidden">

        {{-- Leaflet map --}}
        <div id="map-container"></div>

        {{-- ── Right-side HUD ──────────────────────────────────────── --}}
        <div class="absolute right-4 top-1/2 -translate-y-1/2 flex flex-col gap-3 z-30">
            <div class="flex flex-col bg-[#10131a]/80 backdrop-blur-xl
                        border border-[#424754]/30 rounded-2xl p-1 overflow-hidden">
                <button id="btn-zoom-in"
                    class="min-w-[48px] min-h-[48px] flex items-center justify-center
                           text-[#adc6ff] hover:bg-[#adc6ff]/10 transition-colors"
                    title="Zoom in">
                    <span class="material-symbols-outlined">add</span>
                </button>
                <div class="h-px mx-2 bg-[#424754]/30"></div>
                <button id="btn-zoom-out"
                    class="min-w-[48px] min-h-[48px] flex items-center justify-center
                           text-[#adc6ff] hover:bg-[#adc6ff]/10 transition-colors"
                    title="Zoom out">
                    <span class="material-symbols-outlined">remove</span>
                </button>
            </div>

            <button id="btn-center-drone"
                class="min-w-[48px] min-h-[48px] bg-[#10131a]/80 backdrop-blur-xl
                       border border-[#424754]/30 rounded-2xl
                       flex items-center justify-center
                       text-[#e1e2ec] hover:text-[#adc6ff] transition-colors"
                title="Center on drone">
                <span class="material-symbols-outlined">my_location</span>
            </button>
        </div>

        {{-- ── Top-left: drone name + status chip ─────────────────── --}}
        <div class="absolute left-4 top-6 z-30 flex gap-3 items-center">
            <div class="bg-[#10131a]/80 backdrop-blur-xl border border-[#424754]/30
                        rounded-2xl px-4 py-2 flex items-center gap-3">
                @php $color = match($status) {
                    'active'      => '#22c55e',
                    'emergency'   => '#ef4444',
                    'maintenance' => '#eab308',
                    default       => '#6b7280',
                }; @endphp
                <span class="w-2.5 h-2.5 rounded-full {{ $dotClass }}"></span>
                <span class="text-[20px] leading-[28px] font-semibold text-[#e1e2ec]">
                    {{ $drone->name }}
                </span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded border {{ $badgeClass }}">
                    {{ strtoupper($statusLabel) }}
                </span>
            </div>
        </div>

        {{-- ── Bottom sheet: detailed telemetry stats ─────────────── --}}
        <div id="bottom-sheet"
            class="absolute bottom-0 left-0 right-0 z-[60]
                   bg-[#272a31] rounded-t-3xl border-t border-[#424754]/30
                   shadow-2xl bottom-sheet-transition translate-y-full
                   flex flex-col max-h-[65vh]">

            {{-- Handle --}}
            <div class="w-full flex justify-center py-3 cursor-pointer"
                 id="sheet-handle">
                <div class="w-12 h-1.5 bg-[#424754]/50 rounded-full"></div>
            </div>

            <div class="px-4 pb-8 overflow-y-auto">
                {{-- Header --}}
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-[32px] leading-[40px] tracking-[-0.02em] font-bold text-[#adc6ff]">
                            {{ $drone->name }} Flight Details
                        </h3>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-[#c2c6d6] mt-1">
                            Status: {{ ucfirst($status) }}
                            @if($drone->geofence) · Zone: {{ $drone->geofence->name }} @endif
                        </p>
                    </div>
                    <button id="sheet-close"
                        class="min-w-[48px] min-h-[48px] bg-[#32353c]/30 rounded-full
                               flex items-center justify-center text-[#c2c6d6] hover:text-[#e1e2ec]
                               transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                {{-- Bento grid stats --}}
                <div class="grid grid-cols-3 gap-4 mb-6">
                    {{-- Battery --}}
                    <div class="bg-[#10131a] p-6 rounded-2xl border border-[#424754]/20">
                        <span class="text-[#c2c6d6] text-[10px] font-bold uppercase block mb-2">Battery Health</span>
                        <div class="flex items-end gap-2">
                            <span class="text-3xl font-mono {{ $batteryTextClass }}">{{ $battery }}%</span>
                            <span class="material-symbols-outlined {{ $batteryTextClass }} mb-1">battery_charging_full</span>
                        </div>
                        <div class="mt-4 h-1 w-full bg-[#424754]/30 rounded-full overflow-hidden">
                            <div class="h-full {{ $batteryFillClass }} rounded-full" style="width: {{ $battery }}%"></div>
                        </div>
                    </div>

                    {{-- Altitude --}}
                    <div class="bg-[#10131a] p-6 rounded-2xl border border-[#424754]/20">
                        <span class="text-[#c2c6d6] text-[10px] font-bold uppercase block mb-2">Altitude</span>
                        <div class="flex items-end gap-2">
                            <span class="text-3xl font-mono text-[#e1e2ec]">
                                {{ $drone->current_altitude ? round($drone->current_altitude) : '—' }}
                            </span>
                            <span class="text-[#c2c6d6] text-[11px] font-bold mb-1">M</span>
                        </div>
                        <div class="mt-4 flex gap-1 items-end">
                            @for($i = 0; $i < 5; $i++)
                                <div class="w-1 rounded-full {{ $i < 4 ? 'bg-[#adc6ff]' : 'bg-[#424754]/30' }}"
                                     style="height: {{ ($i + 1) * 5 }}px"></div>
                            @endfor
                        </div>
                    </div>

                    {{-- Signal --}}
                    @php $lastTelemetry = $recentTelemetry->first(); @endphp
                    <div class="bg-[#10131a] p-6 rounded-2xl border border-[#424754]/20">
                        <span class="text-[#c2c6d6] text-[10px] font-bold uppercase block mb-2">Signal</span>
                        <div class="flex items-end gap-2">
                            <span class="text-3xl font-mono text-[#e1e2ec]">
                                {{ $lastTelemetry?->signal_strength ?? '—' }}
                            </span>
                            @if($lastTelemetry)
                                <span class="text-[#c2c6d6] text-[11px] font-bold mb-1">%</span>
                            @endif
                        </div>
                        <div class="mt-4 flex gap-1 items-end">
                            @php $sig = $lastTelemetry?->signal_strength ?? 0; $bars = (int) round($sig / 20); @endphp
                            @for($i = 0; $i < 5; $i++)
                                <div class="w-1 rounded-full {{ $i < $bars ? 'bg-[#adc6ff]' : 'bg-[#424754]/30' }}"
                                     style="height: {{ ($i + 1) * 5 }}px"></div>
                            @endfor
                        </div>
                    </div>
                </div>

                {{-- Position + last update --}}
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-[#10131a] p-4 rounded-2xl border border-[#424754]/20">
                        <span class="text-[#c2c6d6] text-[10px] font-bold uppercase block mb-2">Position</span>
                        @if($drone->current_lat && $drone->current_lng)
                            <p class="font-mono text-[13px] text-[#e1e2ec]">
                                {{ number_format((float)$drone->current_lat, 6) }},
                                {{ number_format((float)$drone->current_lng, 6) }}
                            </p>
                        @else
                            <p class="font-mono text-[13px] text-[#8c909f]">No position data</p>
                        @endif
                    </div>
                    <div class="bg-[#10131a] p-4 rounded-2xl border border-[#424754]/20">
                        <span class="text-[#c2c6d6] text-[10px] font-bold uppercase block mb-2">Last Update</span>
                        <p class="font-mono text-[13px] text-[#e1e2ec]">
                            {{ $drone->last_telemetry_at?->diffForHumans() ?? 'Never' }}
                        </p>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="flex gap-4">
                    <button class="flex-1 bg-[#adc6ff] text-[#002e6a] py-5 rounded-2xl
                                   font-bold text-[11px] uppercase tracking-widest
                                   shadow-lg shadow-[#adc6ff]/20
                                   flex items-center justify-center gap-3
                                   active:scale-[0.98] transition-transform">
                        <span class="material-symbols-outlined">near_me</span>
                        Return to Home
                    </button>
                    <button class="flex-1 border-2 border-[#adc6ff] text-[#adc6ff] py-5 rounded-2xl
                                   font-bold text-[11px] uppercase tracking-widest
                                   hover:bg-[#adc6ff]/5
                                   flex items-center justify-center gap-3">
                        <span class="material-symbols-outlined">videocam</span>
                        Live Feed
                    </button>
                    <button class="px-6 border-2 border-[#ffb4ab] text-[#ffb4ab] py-5 rounded-2xl
                                   font-bold text-[11px] uppercase
                                   hover:bg-[#ffb4ab]/5
                                   flex items-center justify-center">
                        <span class="material-symbols-outlined">stop_circle</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Sheet backdrop --}}
        <div id="sheet-backdrop"
            class="absolute inset-0 bg-black/50 z-50 opacity-0 pointer-events-none transition-opacity"></div>
    </div>
@endsection

{{-- ── Bootstrap data for map.js ─────────────────────────────────── --}}
@php
$droneData = [
    'id'       => $drone->id,
    'name'     => $drone->name,
    'status'   => (string) ($drone->status?->value ?? $drone->status),
    'lat'      => $drone->current_lat !== null ? (float) $drone->current_lat : null,
    'lng'      => $drone->current_lng !== null ? (float) $drone->current_lng : null,
    'battery'  => (int) $drone->battery_level,
    'altitude' => $drone->current_altitude !== null ? (float) $drone->current_altitude : null,
    'geofence' => $drone->geofence ? [
        'id'            => $drone->geofence->id,
        'name'          => $drone->geofence->name,
        'boundary'      => $drone->geofence->boundary,
        'center_lat'    => $drone->geofence->center_lat !== null ? (float) $drone->geofence->center_lat : null,
        'center_lng'    => $drone->geofence->center_lng !== null ? (float) $drone->geofence->center_lng : null,
        'radius_meters' => $drone->geofence->radius_meters !== null ? (float) $drone->geofence->radius_meters : null,
    ] : null,
];

$trailData = $recentTelemetry->map(fn ($t) => [
    (float) $t->latitude,
    (float) $t->longitude,
])->values()->toArray();
@endphp

@push('map-data')
<script>
    window.mapConfig = {
        mode: 'single',
        focusedDroneId: {{ $drone->id }},
        drone: @json($droneData),
        trailData: @json($trailData),
    };
</script>
@endpush

{{-- ── Page-specific JS ────────────────────────────────────────────── --}}
@push('scripts')
    @vite('resources/js/map-show.js')
@endpush
