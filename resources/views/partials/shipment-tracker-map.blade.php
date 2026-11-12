@php
    $steps = ['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'];
    $currentIndex = array_search($shipment->current_status, $steps, true);
    if ($currentIndex === false) {
        $currentIndex = -1;
    }

    $eventsForMap = $shipment->trackingEvents->map(function ($ev) {
        return [
            'id' => $ev->id,
            'status' => $ev->status,
            'event_time' => $ev->event_time?->format('M d, Y H:i'),
            'location_name' => $ev->location ? "{$ev->location->name} ({$ev->location->city})" : 'Transit Coordinate',
            'latitude' => $ev->latitude ?? $ev->location?->latitude,
            'longitude' => $ev->longitude ?? $ev->location?->longitude,
            'description' => $ev->description,
            'is_exception' => (bool) $ev->is_exception,
        ];
    })->filter(fn ($e) => $e['latitude'] !== null && $e['longitude'] !== null)->values();

    $weatherEndpoint = $weatherUrl ?? route('web.shipments.weather', $shipment);
@endphp

<!-- Visual Step-by-Step Progress Bar -->
<div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
        <div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Waybill / Shipment Number</div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">{{ $shipment->shipment_number }}</div>
        </div>
        <div class="flex items-center gap-2">
            @include('partials.status-badge', ['status' => $shipment->current_status])
            @if($shipment->is_delayed)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                    DELAYED +{{ $shipment->delay_hours }}h ({{ $shipment->delay_severity }})
                </span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    ON SCHEDULE
                </span>
            @endif
        </div>
    </div>

    @if($shipment->current_status === 'CANCELLED')
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold">
            This shipment has been cancelled.
        </div>
    @else
        <div class="grid grid-cols-5 gap-2 relative">
            @foreach($steps as $idx => $step)
                @php
                    $completed = $idx <= $currentIndex;
                    $isCurrent = $idx === $currentIndex;
                @endphp
                <div class="flex flex-col items-center text-center">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-black border-2 transition
                        {{ $completed ? 'bg-[#FF5500] border-[#FF5500] text-white shadow-md shadow-orange-500/30' : 'bg-slate-100 border-slate-300 text-slate-400' }}
                        {{ $isCurrent ? 'ring-4 ring-orange-200' : '' }}">
                        {{ $idx + 1 }}
                    </div>
                    <div class="mt-2 text-[11px] font-bold {{ $completed ? 'text-slate-900' : 'text-slate-400' }}">
                        {{ str_replace('_', ' ', $step) }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Interactive Leaflet.js + OpenStreetMap Route & Live Weather -->
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm mb-6">
    <div class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-bold text-slate-900">Live Global Route Map & Weather Telemetry</h3>
            <p class="text-xs text-slate-500">Click any marker to inspect checkpoint timestamp, description, and real-time Open-Meteo weather.</p>
        </div>
        <div class="flex items-center gap-3 text-[11px] font-semibold text-slate-600">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Origin</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-sky-500 inline-block"></span> Checkpoint</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-red-600 inline-block"></span> Exception</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-[#FF5500] inline-block"></span> Live Position</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-rose-700 inline-block"></span> Destination</span>
        </div>
    </div>
    <div id="klx-shipment-map" class="w-full h-[420px] z-10"></div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mapEl = document.getElementById('klx-shipment-map');
    if (!mapEl || typeof L === 'undefined') return;

    const origin = @json([
        'name' => $shipment->originLocation?->name,
        'city' => $shipment->originLocation?->city,
        'country' => $shipment->originLocation?->country,
        'lat' => $shipment->originLocation?->latitude,
        'lng' => $shipment->originLocation?->longitude,
    ]);
    const destination = @json([
        'name' => $shipment->destinationLocation?->name,
        'city' => $shipment->destinationLocation?->city,
        'country' => $shipment->destinationLocation?->country,
        'lat' => $shipment->destinationLocation?->latitude,
        'lng' => $shipment->destinationLocation?->longitude,
    ]);
    const events = @json($eventsForMap);
    const weatherBaseUrl = @json($weatherEndpoint);

    const map = L.map('klx-shipment-map', { scrollWheelZoom: true }).setView([0, 100], 3);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    const makeIcon = (bgColor, label, extraClass = '') => L.divIcon({
        className: 'custom-klx-pin',
        html: `<div class="${extraClass}" style="background:${bgColor};color:#fff;border:2px solid #fff;border-radius:9999px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;box-shadow:0 3px 10px rgba(0,0,0,0.35);">${label}</div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        popupAnchor: [0, -14],
    });

    const attachWeatherPopup = (marker, title, status, timestamp, locationName, description, lat, lng) => {
        const safeDesc = String(description || '').replace(/</g, '&lt;');
        const safeLoc = String(locationName || '').replace(/</g, '&lt;');
        const containerId = `weather-pop-${Math.random().toString(36).slice(2, 9)}`;

        marker.bindPopup(`
            <div style="min-width:220px;font-family:system-ui,sans-serif;">
                <div style="font-weight:800;font-size:13px;color:#0f172a;margin-bottom:2px;">${title}</div>
                <div style="font-size:11px;font-weight:700;color:#FF5500;margin-bottom:4px;">${status} &bull; ${timestamp}</div>
                <div style="font-size:12px;font-weight:600;color:#334155;">${safeLoc}</div>
                <div style="font-size:12px;color:#475569;margin-top:4px;">${safeDesc}</div>
                <div id="${containerId}" style="margin-top:8px;padding-top:6px;border-top:1px solid #e2e8f0;font-size:11px;color:#0369a1;">
                    Loading live weather...
                </div>
            </div>
        `);

        marker.on('popupopen', async () => {
            const target = document.getElementById(containerId);
            if (!target) return;
            try {
                const res = await fetch(`${weatherBaseUrl}?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const payload = await res.json();
                const w = payload.data || {};
                if (w.available) {
                    target.innerHTML = `<strong>Weather (${w.source}):</strong> ${w.condition}, ${w.temperature_c}&deg;C, Wind ${w.windspeed_kmh} km/h`;
                } else {
                    target.innerHTML = `<span style="color:#64748b;">Weather: ${w.condition || 'Unavailable'}</span>`;
                }
            } catch (e) {
                target.innerHTML = `<span style="color:#64748b;">Weather unavailable offline.</span>`;
            }
        });
    };

    const bounds = [];
    const traveledPath = [];

    if (origin.lat && origin.lng) {
        const originCoord = [origin.lat, origin.lng];
        bounds.push(originCoord);
        traveledPath.push(originCoord);
        const originMarker = L.marker(originCoord, { icon: makeIcon('#10b981', 'O') }).addTo(map);
        attachWeatherPopup(
            originMarker,
            'Origin Facility',
            'ORIGIN',
            'Scheduled Departure',
            `${origin.name} (${origin.city}, ${origin.country})`,
            'Origin hub dispatch point.',
            origin.lat,
            origin.lng
        );
    }

    events.forEach((ev, index) => {
        const coord = [ev.latitude, ev.longitude];
        bounds.push(coord);
        traveledPath.push(coord);
        const isLast = index === events.length - 1;

        let color = ev.is_exception ? '#dc2626' : '#0284c7';
        let label = ev.is_exception ? '!' : String(index + 1);
        let extraClass = '';
        let title = ev.is_exception ? 'Exception Alert' : `Checkpoint #${index + 1}`;

        if (isLast) {
            color = ev.is_exception ? '#dc2626' : '#FF5500';
            label = 'KLX';
            extraClass = 'klx-pulse-marker';
            title = 'Current Package Position';
        }

        const m = L.marker(coord, { icon: makeIcon(color, label, extraClass) }).addTo(map);
        attachWeatherPopup(m, title, ev.status, ev.event_time, ev.location_name, ev.description, ev.latitude, ev.longitude);
    });

    if (destination.lat && destination.lng) {
        const destCoord = [destination.lat, destination.lng];
        bounds.push(destCoord);
        const destMarker = L.marker(destCoord, { icon: makeIcon('#be123c', 'D') }).addTo(map);
        attachWeatherPopup(
            destMarker,
            'Destination Facility',
            'DESTINATION',
            'Target Arrival',
            `${destination.name} (${destination.city}, ${destination.country})`,
            'Final consignee hub destination.',
            destination.lat,
            destination.lng
        );

        const lastTraveled = traveledPath.length > 0 ? traveledPath[traveledPath.length - 1] : null;
        if (lastTraveled) {
            L.polyline([lastTraveled, destCoord], {
                color: '#0f172a',
                weight: 3,
                opacity: 0.55,
                dashArray: '8, 8'
            }).addTo(map);
        }
    }

    if (traveledPath.length > 1) {
        L.polyline(traveledPath, {
            color: '#FF5500',
            weight: 4,
            opacity: 0.9
        }).addTo(map);
    }

    if (bounds.length > 0) {
        map.fitBounds(bounds, { padding: [45, 45], maxZoom: 11 });
    }
});
</script>
@endpush
