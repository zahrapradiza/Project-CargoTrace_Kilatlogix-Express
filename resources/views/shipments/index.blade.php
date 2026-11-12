@extends($layout)

@section('title', 'Shipments')
@section('header', 'Global Shipments Directory')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
            <form method="GET" class="flex flex-wrap items-center gap-2 flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Waybill or cargo type..."
                    class="px-3.5 py-2 rounded-xl border border-slate-300 text-sm w-56">
                <select name="status" class="px-3 py-2 rounded-xl border border-slate-300 text-sm">
                    <option value="">All Statuses</option>
                    @foreach(config('cargotrace.statuses') as $st)
                        <option value="{{ $st }}" @selected(request('status') === $st)>{{ $st }}</option>
                    @endforeach
                </select>
                <select name="carrier_id" class="px-3 py-2 rounded-xl border border-slate-300 text-sm">
                    <option value="">All Carriers</option>
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->id }}" @selected((string) request('carrier_id') === (string) $carrier->id)>{{ $carrier->name }}</option>
                    @endforeach
                </select>
                <select name="delayed" class="px-3 py-2 rounded-xl border border-slate-300 text-sm">
                    <option value="">All Schedules</option>
                    <option value="1" @selected(request('delayed') === '1')>Delayed Only</option>
                    <option value="0" @selected(request('delayed') === '0')>On Schedule</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-bold">Filter</button>
            </form>

            @can('create', App\Models\Shipment::class)
                <a href="{{ route('shipments.create') }}" class="px-4 py-2.5 rounded-xl bg-[#FF5500] hover:bg-orange-600 text-white text-sm font-bold shadow-sm transition">
                    + New Shipment
                </a>
            @endcan
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-3">Waybill</th>
                        <th class="py-3 px-3">Route</th>
                        <th class="py-3 px-3">Carrier / Cargo</th>
                        <th class="py-3 px-3">Customer</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3">Delay / ETA</th>
                        <th class="py-3 px-3 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($shipments as $shipment)
                        <tr class="hover:bg-slate-50/80">
                            <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ $shipment->shipment_number }}</td>
                            <td class="py-3 px-3 text-xs">
                                <span class="font-semibold">{{ $shipment->originLocation->city }}</span> &rarr;
                                <span class="font-semibold">{{ $shipment->destinationLocation->city }}</span>
                            </td>
                            <td class="py-3 px-3 text-xs">
                                <div class="font-semibold text-slate-800">{{ $shipment->carrier->name }}</div>
                                <div class="text-slate-500">{{ $shipment->cargo_type }} ({{ $shipment->weight_kg }} kg)</div>
                            </td>
                            <td class="py-3 px-3 text-xs">{{ $shipment->customer->name }}</td>
                            <td class="py-3 px-3">@include('partials.status-badge', ['status' => $shipment->current_status])</td>
                            <td class="py-3 px-3 text-xs">
                                @if($shipment->is_delayed)
                                    <span class="px-2 py-0.5 rounded-full font-bold bg-rose-100 text-rose-800">+{{ $shipment->delay_hours }}h ({{ $shipment->delay_severity }})</span>
                                @else
                                    <span class="text-emerald-700 font-semibold">On Time</span>
                                @endif
                                <div class="text-slate-400 mt-0.5">ETA: {{ ($shipment->estimated_arrival ?? $shipment->expected_arrival)?->format('M d, H:i') }}</div>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <a href="{{ route('shipments.show', $shipment) }}" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-[#FF5500] text-white text-xs font-bold transition">
                                    Track &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No shipments matched your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $shipments->links() }}</div>
    </div>
@endsection
