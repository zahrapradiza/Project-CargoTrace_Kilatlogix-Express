@extends('layouts.operator')

@section('title', 'Hub Operations Queue')
@section('header', 'Hub Operations & Dispatch Queue')

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Shipments Requiring Operational Follow-Up -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-teal-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900">Shipments Requiring Hub Action</h3>
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
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">+{{ $shipment->delay_hours }}h</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-600 mt-1">
                                {{ $shipment->originLocation->city }} &rarr; {{ $shipment->destinationLocation->city }}
                                &bull; Customer: {{ $shipment->customer->name }}
                            </div>
                        </div>
                        <a href="{{ route('shipments.show', $shipment) }}" class="px-3.5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition">
                            Scan / Update
                        </a>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No urgent operational follow-ups right now.</p>
                @endforelse
            </div>
        </div>

        <!-- Active Exception Desk -->
        <div class="bg-white rounded-2xl border border-amber-200 p-6 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-4">Active Exception Alerts</h3>
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
                        <p class="text-slate-600 mt-1">{{ $ex->description }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">All exceptions resolved.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
