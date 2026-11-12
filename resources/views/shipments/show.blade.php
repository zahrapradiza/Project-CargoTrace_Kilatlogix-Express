@extends($layout)

@section('title', $shipment->shipment_number)
@section('header', "Shipment {$shipment->shipment_number}")

@section('content')
    @include('partials.shipment-tracker-map', ['shipment' => $shipment])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Metadata, Weather & Operational Actions -->
        <div class="space-y-6">
            <!-- Route & ETA Card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm text-sm space-y-3">
                <h3 class="font-bold text-slate-900 border-b border-slate-100 pb-2">Waybill & Schedule Details</h3>
                <div class="flex justify-between"><span class="text-slate-500">Customer</span><span class="font-bold">{{ $shipment->customer->name }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Carrier</span><span class="font-bold">{{ $shipment->carrier->name }} ({{ $shipment->carrier->transport_mode }})</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Origin</span><span class="font-semibold text-right">{{ $shipment->originLocation->name }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Destination</span><span class="font-semibold text-right">{{ $shipment->destinationLocation->name }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Cargo / Weight</span><span class="font-semibold">{{ $shipment->cargo_type }} ({{ $shipment->weight_kg }} kg)</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Expected Arrival</span><span class="font-mono text-xs">{{ $shipment->expected_arrival?->format('Y-m-d H:i') }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Estimated Arrival</span><span class="font-mono text-xs font-bold {{ $shipment->is_delayed ? 'text-rose-600' : 'text-emerald-600' }}">{{ $shipment->estimated_arrival?->format('Y-m-d H:i') ?? 'TBD' }}</span></div>
                @if($shipment->is_delayed)
                    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800">
                        <div class="font-bold">Delay Notice (+{{ $shipment->delay_hours }}h — {{ $shipment->delay_severity }})</div>
                        <div class="mt-0.5">{{ $shipment->delay_reason }}</div>
                    </div>
                @endif
            </div>

            <!-- Live Weather at Latest Location -->
            @if($latestLat !== null && $latestLng !== null)
                <div id="klx-weather-card" class="bg-gradient-to-br from-sky-900 to-slate-900 text-white rounded-2xl p-6 shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-sky-300 mb-1">Latest Location Weather</div>
                    <div id="klx-weather-main" class="text-lg font-black">Loading live weather...</div>
                    <div id="klx-weather-sub" class="text-xs text-sky-200 mt-1">Coords: {{ $latestLat }}, {{ $latestLng }}</div>
                </div>
                @push('scripts')
                <script>
                document.addEventListener('DOMContentLoaded', async () => {
                    const mainEl = document.getElementById('klx-weather-main');
                    const subEl = document.getElementById('klx-weather-sub');
                    try {
                        const res = await fetch(`{{ route('web.shipments.weather', $shipment) }}?lat={{ $latestLat }}&lng={{ $latestLng }}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const payload = await res.json();
                        const w = payload.data || {};
                        if (w.available) {
                            mainEl.textContent = `${w.temperature_c}°C • ${w.condition}`;
                            subEl.textContent = `Wind: ${w.windspeed_kmh} km/h • Source: ${w.source}`;
                        } else {
                            mainEl.textContent = w.condition || 'Weather data unavailable';
                            subEl.textContent = w.note || 'Offline fallback mode active.';
                        }
                    } catch (e) {
                        mainEl.textContent = 'Weather data unavailable';
                        subEl.textContent = 'Offline fallback mode active.';
                    }
                });
                </script>
                @endpush
            @endif

            <!-- Cargo Items Manifest -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="font-bold text-slate-900 mb-3 text-sm">Cargo Items Manifest</h3>
                <div class="space-y-2 text-xs">
                    @forelse($shipment->items as $item)
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex justify-between">
                            <div>
                                <div class="font-bold text-slate-900">{{ $item->item_name }}</div>
                                <div class="text-slate-500 font-mono">SKU: {{ $item->sku }} &bull; Qty: {{ $item->quantity }}</div>
                            </div>
                            <div class="font-bold text-slate-700">{{ $item->weight_kg }} kg</div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">No itemized manifest recorded.</p>
                    @endforelse
                </div>
            </div>

            <!-- Admin & Operator Only: Update ETA / Archive -->
            @can('update', $shipment)
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="font-bold text-slate-900 mb-3 text-sm">Update ETA &amp; Schedule</h3>
                    <form method="POST" action="{{ route('shipments.update', $shipment) }}" class="space-y-3 text-xs">
                        @csrf @method('PUT')
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Estimated Arrival (ETA)</label>
                            <input type="datetime-local" name="estimated_arrival"
                                value="{{ $shipment->estimated_arrival?->format('Y-m-d\TH:i') }}"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        </div>
                        <div>
                            <label class="block font-bold text-slate-600 mb-1">Delay Reason (optional)</label>
                            <input type="text" name="delay_reason" value="{{ $shipment->delay_reason }}" placeholder="e.g. Port congestion / Weather"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        </div>
                        <button type="submit" class="w-full py-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold">
                            Recalculate ETA &amp; Save
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <!-- Right Column: Operational Forms + Timeline + Exceptions -->
        <div class="lg:col-span-2 space-y-6">
            @can('addTrackingEvent', $shipment)
                @if(! in_array($shipment->current_status, ['DELIVERED', 'CANCELLED']))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Append Tracking Event Form -->
                        <div class="bg-white rounded-2xl border border-teal-200 p-6 shadow-sm">
                            <h3 class="font-bold text-slate-900 mb-3 text-sm">Append Tracking Checkpoint</h3>
                            <form method="POST" action="{{ route('shipments.tracking.store', $shipment) }}" class="space-y-3 text-xs">
                                @csrf
                                <div>
                                    <label class="block font-bold text-slate-600 mb-1">Next Lifecycle Status</label>
                                    <select name="status" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                                        <option value="BOOKED">BOOKED</option>
                                        <option value="PICKED_UP">PICKED_UP</option>
                                        <option value="IN_TRANSIT" selected>IN_TRANSIT</option>
                                        <option value="DELIVERED">DELIVERED</option>
                                        <option value="EXCEPTION">EXCEPTION</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-600 mb-1">Hub / Location</label>
                                    <select name="location_id" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->code }} — {{ $loc->name }} ({{ $loc->city }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-600 mb-1">Checkpoint Description</label>
                                    <textarea name="description" rows="2" required placeholder="Arrived at hub sorting facility..." class="w-full px-3 py-2 rounded-lg border border-slate-300"></textarea>
                                </div>
                                <button type="submit" class="w-full py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold">
                                    Append Tracking Event
                                </button>
                            </form>
                        </div>

                        <!-- Open Exception Form -->
                        <div class="bg-white rounded-2xl border border-rose-200 p-6 shadow-sm">
                            <h3 class="font-bold text-rose-900 mb-3 text-sm">Report Shipment Exception</h3>
                            <form method="POST" action="{{ route('shipments.exceptions.store', $shipment) }}" class="space-y-3 text-xs">
                                @csrf
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Type</label>
                                        <select name="exception_type" required class="w-full px-2.5 py-2 rounded-lg border border-slate-300">
                                            <option value="CUSTOMS_HOLD">CUSTOMS_HOLD</option>
                                            <option value="WEATHER_DELAY">WEATHER_DELAY</option>
                                            <option value="PORT_CONGESTION">PORT_CONGESTION</option>
                                            <option value="DAMAGED">DAMAGED</option>
                                            <option value="MISROUTED">MISROUTED</option>
                                            <option value="DOCUMENTATION_ISSUE">DOCUMENTATION_ISSUE</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block font-bold text-slate-600 mb-1">Severity</label>
                                        <select name="severity" required class="w-full px-2.5 py-2 rounded-lg border border-slate-300">
                                            <option value="LOW">LOW</option>
                                            <option value="MEDIUM" selected>MEDIUM</option>
                                            <option value="HIGH">HIGH</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-600 mb-1">Location</label>
                                    <select name="location_id" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->code }} — {{ $loc->city }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-bold text-slate-600 mb-1">Exception Details</label>
                                    <textarea name="description" rows="2" required placeholder="Describe hold or incident..." class="w-full px-3 py-2 rounded-lg border border-slate-300"></textarea>
                                </div>
                                <button type="submit" class="w-full py-2.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold">
                                    Log Exception Alert
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan

            <!-- Exceptions History & Resolution -->
            @if($shipment->exceptions->isNotEmpty())
                <div class="bg-white rounded-2xl border border-rose-200 p-6 shadow-sm">
                    <h3 class="font-bold text-rose-900 mb-4">Shipment Exceptions ({{ $shipment->exceptions->count() }})</h3>
                    <div class="space-y-3">
                        @foreach($shipment->exceptions as $ex)
                            <div class="p-4 rounded-xl border {{ $ex->status === 'RESOLVED' ? 'bg-emerald-50/50 border-emerald-200' : 'bg-rose-50 border-rose-200' }} text-xs">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                    <div class="font-black text-slate-900">
                                        {{ str_replace('_', ' ', $ex->exception_type) }} &bull;
                                        <span class="text-rose-700">{{ $ex->severity }}</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full font-bold {{ $ex->status === 'RESOLVED' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-200 text-rose-900' }}">{{ $ex->status }}</span>
                                </div>
                                <p class="text-slate-700 text-sm mb-2">{{ $ex->description }}</p>
                                @if($ex->resolution_notes)
                                    <div class="text-emerald-800 font-semibold">Resolution: {{ $ex->resolution_notes }}</div>
                                @endif

                                @can('manageException', $shipment)
                                    @if($ex->status !== 'RESOLVED')
                                        <form method="POST" action="{{ route('exceptions.update', $ex) }}" class="mt-3 flex flex-wrap gap-2">
                                            @csrf @method('PATCH')
                                            <select name="status" class="px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white">
                                                <option value="INVESTIGATING" @selected($ex->status === 'INVESTIGATING')>INVESTIGATING</option>
                                                <option value="RESOLVED">RESOLVED</option>
                                            </select>
                                            <input type="text" name="resolution_notes" placeholder="Resolution notes..." class="flex-1 px-2.5 py-1.5 rounded-lg border border-slate-300 bg-white">
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 text-white font-bold">Update</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Chronological Tracking Timeline -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="font-bold text-slate-900 mb-4">Historical Tracking Timeline (Append-Only)</h3>
                <div class="space-y-3">
                    @foreach($shipment->trackingEvents->sortByDesc('event_time') as $event)
                        <div class="flex gap-4 p-4 rounded-xl border {{ $event->is_exception ? 'bg-rose-50 border-rose-200' : 'bg-slate-50 border-slate-200' }}">
                            <div class="mt-1.5 shrink-0">
                                <span class="w-3 h-3 rounded-full block {{ $event->is_exception ? 'bg-rose-600' : 'bg-[#FF5500]' }}"></span>
                            </div>
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                    <div class="flex items-center gap-2">
                                        @include('partials.status-badge', ['status' => $event->status])
                                        <span class="text-xs font-bold text-slate-800">{{ $event->location?->name ?? 'Transit Coordinates' }}</span>
                                    </div>
                                    <span class="text-xs font-mono text-slate-500">{{ $event->event_time?->format('M d, Y H:i') }}</span>
                                </div>
                                <p class="text-sm {{ $event->is_exception ? 'text-rose-800 font-semibold' : 'text-slate-700' }}">{{ $event->description }}</p>
                                <div class="text-[11px] text-slate-400 mt-1">
                                    Coords: {{ $event->latitude ?? '—' }}, {{ $event->longitude ?? '—' }} &bull; Logged by: {{ $event->creator?->name ?? 'System' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
