@extends('layouts.map')

@section('title', 'Fleet Map')

{{-- Notification dot if any active alerts exist --}}
@section('notification-dot')
    @if($drones->flatMap->alerts->where('resolved_at', null)->count())
        <span class="absolute top-2 right-2 w-2 h-2 bg-[#ffb4ab] rounded-full"></span>
    @endif
@endsection

{{-- ── Sidebar drone list ─────────────────────────────────────────── --}}
@section('sidebar-drone-list')
    @forelse($drones as $drone)
        @php
            $status   = (string) ($drone->status?->value ?? $drone->status);
            $battery  = (int) $drone->battery_level;
            $segments = 5;
            $filled   = (int) round($battery / 100 * $segments);

            $statusLabel = match($status) {
                'active'      => 'In Flight',
                'emergency'   => 'Critical',
                'maintenance' => 'Maintenance',
                default       => 'Offline',
            };

            $statusColor = match($status) {
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

            $batteryTextClass = match(true) {
                $battery < 25 => 'text-[#ffb4ab]',
                default       => 'text-[#e1e2ec]',
            };
        @endphp

        <div class="mx-1 p-4 bg-[#272a31]/50 border border-[#424754]/20 rounded-xl
                    flex flex-col gap-3 active:scale-95 transition-transform cursor-pointer
                    hover:bg-[#272a31]"
             data-drone-id="{{ $drone->id }}">

            {{-- Name + status badge --}}
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full {{ $statusColor }}"></span>
                    <span class="font-mono text-[13px] leading-[16px] font-medium text-[#e1e2ec]">
                        {{ $drone->name }}
                    </span>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded border {{ $badgeClass }}">
                    {{ strtoupper($statusLabel) }}
                </span>
            </div>

            {{-- Battery bar + altitude --}}
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

            {{-- Link to single-drone view --}}
            <a href="{{ route('map.show', $drone) }}"
               class="text-[10px] font-bold text-[#adc6ff] hover:underline uppercase tracking-widest"
               onclick="event.stopPropagation()">
                View Details →
            </a>
        </div>
    @empty
        <p class="px-4 text-[#8c909f] text-sm">No drones registered.</p>
    @endforelse
@endsection

{{-- ── Map area ────────────────────────────────────────────────────── --}}
@section('map-area')
    <div class="relative w-full h-full overflow-hidden">

        {{-- Leaflet map fills this div --}}
        <div id="map-container"></div>

        {{-- ── Right-side zoom / locate HUD ──────────────────────── --}}
        <div class="absolute right-4 top-1/2 -translate-y-1/2 flex flex-col gap-3 z-30">

            {{-- Zoom +/- --}}
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

            {{-- Fit-bounds --}}
            <button id="btn-fit-all"
                class="min-w-[48px] min-h-[48px] bg-[#10131a]/80 backdrop-blur-xl
                       border border-[#424754]/30 rounded-2xl
                       flex items-center justify-center
                       text-[#e1e2ec] hover:text-[#adc6ff] transition-colors"
                title="Fit all drones">
                <span class="material-symbols-outlined">my_location</span>
            </button>
        </div>

        {{-- ── Compass / weather HUD (top-left of map) ───────────── --}}
        <div class="absolute left-4 top-6 z-30">
            <div class="bg-[#10131a]/80 backdrop-blur-xl border border-[#424754]/30
                        rounded-2xl px-4 py-2 flex items-center gap-3">
                <div class="w-10 h-10 rounded-full border-2 border-[#adc6ff]/40
                            flex items-center justify-center relative">
                    <span class="material-symbols-outlined text-[#adc6ff] text-lg rotate-45">navigation</span>
                    <span class="absolute -top-1 left-1/2 -translate-x-1/2 text-[8px] font-bold text-[#adc6ff]">N</span>
                </div>
                <div class="flex flex-col">
                    <span class="font-mono text-xs text-[#e1e2ec]">Live Map</span>
                    <span class="text-[10px] font-bold uppercase text-[#c2c6d6]">
                        {{ $drones->count() }} drone{{ $drones->count() === 1 ? '' : 's' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ── Geofence legend (bottom-left of map) ───────────────── --}}
        @if($geofences->count())
            <div class="absolute left-4 bottom-6 z-30">
                <div class="bg-[#10131a]/80 backdrop-blur-xl border border-[#424754]/30
                            rounded-2xl px-4 py-3">
                    <p class="text-[10px] font-bold text-[#8c909f] uppercase tracking-widest mb-2">Geofences</p>
                    @foreach($geofences as $geofence)
                        <div class="flex items-center gap-2 mb-1 last:mb-0">
                            <span class="w-3 h-3 rounded-sm border border-blue-400 bg-blue-400/10 shrink-0"></span>
                            <span class="text-[11px] text-[#c2c6d6]">{{ $geofence->name }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection

{{-- ── Bootstrap data for map.js ─────────────────────────────────── --}}
@push('map-data')
<script>
@php
$dronesData = $drones->map(fn ($d) => [
    'id'       => $d->id,
    'name'     => $d->name,
    'status'   => (string) ($d->status?->value ?? $d->status),
    'lat'      => $d->current_lat !== null ? (float) $d->current_lat : null,
    'lng'      => $d->current_lng !== null ? (float) $d->current_lng : null,
    'battery'  => (int) $d->battery_level,
    'altitude' => $d->current_altitude !== null ? (float) $d->current_altitude : null,
    'geofence' => $d->geofence ? [
        'id'            => $d->geofence->id,
        'name'          => $d->geofence->name,
        'boundary'      => $d->geofence->boundary,
        'center_lat'    => $d->geofence->center_lat !== null ? (float) $d->geofence->center_lat : null,
        'center_lng'    => $d->geofence->center_lng !== null ? (float) $d->geofence->center_lng : null,
        'radius_meters' => $d->geofence->radius_meters !== null ? (float) $d->geofence->radius_meters : null,
    ] : null,
])->toArray();

$geofencesData = $geofences->map(fn ($g) => [
    'id'            => $g->id,
    'name'          => $g->name,
    'boundary'      => $g->boundary,
    'center_lat'    => $g->center_lat !== null ? (float) $g->center_lat : null,
    'center_lng'    => $g->center_lng !== null ? (float) $g->center_lng : null,
    'radius_meters' => $g->radius_meters !== null ? (float) $g->radius_meters : null,
])->toArray();
@endphp

    window.mapConfig = {
        mode: 'fleet',
        dronesData: @json($dronesData),
        geofencesData: @json($geofencesData),
    };
</script>
@endpush

{{-- ── Page-specific JS ────────────────────────────────────────────── --}}
@push('scripts')
    @vite('resources/js/map-index.js')
@endpush
