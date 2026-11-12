@extends('layouts.operator')

@section('title', 'Hub Operations Queue')
@section('header', 'Hub Operations & Dispatch Queue')

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-teal-200 p-5 shadow-sm">
            <div class="text-xs font-bold uppercase text-teal-700">Active Shipments</div>
            <div class="text-3xl font-black text-slate-900 mt-1">{{ $activeCount }}</div>
            <div class="text-xs text-slate-500 mt-1">In pipeline</div>
        </div>
        <div class="bg-white rounded-2xl border border-rose-200 p-5 shadow-sm">
            <div class="text-xs font-bold uppercase text-rose-700">Active Delays</div>
            <div class="text-3xl font-black text-rose-600 mt-1">{{ $delayedCount }}</div>
            <div class="text-xs text-slate-500 mt-1">Need ETA review</div>
        </div>
        <div class="bg-white rounded-2xl border border-amber-200 p-5 shadow-sm">
            <div class="text-xs font-bold uppercase text-amber-700">Open Exceptions</div>
            <div class="text-3xl font-black text-amber-600 mt-1">{{ $openExceptionsCount }}</div>
            <div class="text-xs text-slate-500 mt-1">Customs / Weather / Port</div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 p-5 shadow-sm">
            <div class="text-xs font-bold uppercase text-emerald-700">Delivered Today</div>
            <div class="text-3xl font-black text-emerald-600 mt-1">{{ $deliveredTodayCount }}</div>
            <div class="text-xs text-slate-500 mt-1">Completed handoffs</div>
        </div>
    </div>

    <!-- Quick Hub Scanner & Fast Waybill Creation Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Quick Scan / Update Tracking Checkpoint -->
        <div class="bg-white rounded-2xl border-2 border-teal-500 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-black text-slate-900">Scan / Update Tracking Checkpoint</h3>
                <span class="px-2 py-0.5 rounded bg-teal-100 text-teal-800 text-[10px] font-black uppercase">Hub Scanner</span>
            </div>
            <form id="quick-scan-form" method="POST" action="" class="space-y-3 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Select Active Waybill</label>
                        <select id="scan-shipment-id" required class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                            @foreach($activeShipments as $act)
                                <option value="{{ $act->id }}">{{ $act->shipment_number }} ({{ $act->current_status }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">New Lifecycle Status</label>
                        <select name="status" required class="w-full px-3 py-2 rounded-lg border border-slate-300 font-bold">
                            <option value="BOOKED">BOOKED</option>
                            <option value="PICKED_UP">PICKED_UP</option>
                            <option value="IN_TRANSIT" selected>IN_TRANSIT</option>
                            <option value="DELIVERED">DELIVERED</option>
                            <option value="EXCEPTION">EXCEPTION</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Hub Location</label>
                        <select id="scan-location-id" name="location_id" onchange="syncScanCoords(this)" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" data-lat="{{ $loc->latitude }}" data-lng="{{ $loc->longitude }}">
                                    {{ $loc->code }} — {{ $loc->city }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Latitude</label>
                        <input type="number" step="0.0000001" id="scan-lat" name="latitude" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Longitude</label>
                        <input type="number" step="0.0000001" id="scan-lng" name="longitude" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-600 mb-1">Checkpoint Notes</label>
                    <input type="text" name="description" required placeholder="Scanned at drop point & dispatched to outbound carrier..." class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow transition">
                    Scan &amp; Append Tracking Checkpoint
                </button>
            </form>
        </div>

        <!-- Quick Register New Waybill -->
        <div class="bg-white rounded-2xl border border-sky-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-black text-slate-900">Quick Waybill Registration</h3>
                <a href="{{ route('shipments.create') }}" class="text-xs font-bold text-sky-700 hover:underline">Full Form + Items &rarr;</a>
            </div>
            <form method="POST" action="{{ route('shipments.store') }}" class="space-y-3 text-xs">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Customer</label>
                        <select name="customer_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Carrier</label>
                        <select name="carrier_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            @foreach($carriers as $car)
                                <option value="{{ $car->id }}">{{ $car->name }} ({{ $car->transport_mode }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Origin</label>
                        <select name="origin_location_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->code }} — {{ $loc->city }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Destination</label>
                        <select name="destination_location_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                            @foreach($locations as $idx => $loc)
                                <option value="{{ $loc->id }}" @selected($idx === 1)>{{ $loc->code }} — {{ $loc->city }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block font-bold text-slate-600 mb-1">Cargo Type</label>
                        <input type="text" name="cargo_type" value="Commercial Parcel" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-600 mb-1">Weight (kg)</label>
                        <input type="number" step="0.01" name="weight_kg" value="45" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                </div>
                <input type="hidden" name="expected_departure" value="{{ now()->format('Y-m-d\TH:i') }}">
                <input type="hidden" name="expected_arrival" value="{{ now()->addDays(4)->format('Y-m-d\TH:i') }}">
                <button type="submit" class="w-full py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs shadow transition">
                    + Generate Waybill Now
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Shipments Requiring Operational Follow-Up -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-teal-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900">Priority Delayed &amp; Active Shipments</h3>
                <a href="{{ route('shipments.index') }}" class="text-xs font-bold text-teal-700 hover:underline">All Shipments &rarr;</a>
            </div>
            <div class="space-y-3">
                @forelse($followUpShipments as $shipment)
                    <div class="p-4 rounded-xl border {{ $shipment->is_delayed ? 'border-rose-200 bg-rose-50/40' : 'border-slate-200 bg-slate-50/50' }} flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('shipments.show', $shipment) }}" class="font-mono font-black text-slate-900 hover:text-teal-700">
                                    {{ $shipment->shipment_number }}
                                </a>
                                @include('partials.status-badge', ['status' => $shipment->current_status])
                                @if($shipment->is_delayed)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">+{{ $shipment->delay_hours }}h ({{ $shipment->delay_severity }})</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-600 mt-1">
                                {{ $shipment->originLocation->city }} &rarr; {{ $shipment->destinationLocation->city }}
                                &bull; Customer: {{ $shipment->customer->name }}
                            </div>
                        </div>
                        <a href="{{ route('shipments.show', $shipment) }}" class="px-3.5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition">
                            Open Detail
                        </a>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No urgent operational follow-ups right now.</p>
                @endforelse
            </div>
        </div>

        <!-- Active Exception Desk with Inline Resolution -->
        <div class="bg-white rounded-2xl border border-amber-200 p-6 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-4">Active Exceptions (Update Status)</h3>
            <div class="space-y-3">
                @forelse($openExceptions as $ex)
                    <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs">
                        <div class="flex items-center justify-between mb-1">
                            <a href="{{ route('shipments.show', $ex->shipment) }}" class="font-mono font-bold text-slate-900 hover:underline">
                                {{ $ex->shipment->shipment_number }}
                            </a>
                            <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-800 font-bold">{{ $ex->severity }}</span>
                        </div>
                        <div class="font-bold text-amber-900">{{ str_replace('_', ' ', $ex->exception_type) }} ({{ $ex->status }})</div>
                        <p class="text-slate-600 mt-1 mb-2">{{ $ex->description }}</p>

                        <form method="POST" action="{{ route('exceptions.update', $ex) }}" class="space-y-1.5 pt-2 border-t border-amber-200/70">
                            @csrf @method('PATCH')
                            <div class="flex gap-1.5">
                                <select name="status" class="px-2 py-1 rounded border border-amber-300 bg-white font-semibold">
                                    <option value="OPEN" @selected($ex->status === 'OPEN')>OPEN</option>
                                    <option value="INVESTIGATING" @selected($ex->status === 'INVESTIGATING')>INVESTIGATING</option>
                                    <option value="RESOLVED">RESOLVED</option>
                                </select>
                                <input type="text" name="resolution_notes" placeholder="Notes..." class="flex-1 px-2 py-1 rounded border border-amber-300 bg-white">
                            </div>
                            <button type="submit" class="w-full py-1.5 rounded bg-slate-900 hover:bg-teal-700 text-white font-bold transition">
                                Update Exception
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">All exceptions resolved.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function syncScanCoords(selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    if (!opt) return;
    document.getElementById('scan-lat').value = opt.dataset.lat || '';
    document.getElementById('scan-lng').value = opt.dataset.lng || '';
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('quick-scan-form');
    const shipSelect = document.getElementById('scan-shipment-id');
    const locSelect = document.getElementById('scan-location-id');
    const updateAction = () => {
        if (shipSelect && shipSelect.value) {
            form.action = `/shipments/${shipSelect.value}/tracking`;
        }
    };
    if (shipSelect) {
        updateAction();
        shipSelect.addEventListener('change', updateAction);
    }
    if (locSelect) syncScanCoords(locSelect);
});
</script>
@endpush
