@extends('layouts.customer')

@section('title', 'My Shipments Portal')

@section('content')
    <!-- Hero Greeting & Quick Waybill Search -->
    <div class="bg-gradient-to-r from-[#0F172A] to-slate-800 rounded-3xl p-8 text-white mb-8 shadow-xl border-b-4 border-[#FF5500]">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-[#FF5500] mb-1">KilatLogix Express Consumer Portal</div>
                <h1 class="text-2xl sm:text-3xl font-black">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="text-slate-300 text-sm mt-1">Track your active air, ocean, and express cargo shipments with real-time map & weather telemetry.</p>
            </div>
            <form method="GET" action="{{ route('customer.dashboard') }}" class="flex gap-2 w-full lg:w-96">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search your waybill (e.g. KLX-2026-...)"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-white text-slate-900 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#FF5500]">
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#FF5500] hover:bg-orange-600 text-white font-bold text-sm transition">
                    Search
                </button>
            </form>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-6 pt-6 border-t border-slate-700/80 text-center sm:text-left">
            <div>
                <div class="text-xs text-slate-400">My Total Shipments</div>
                <div class="text-2xl font-black">{{ $stats['total'] }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-400">Active In Pipeline</div>
                <div class="text-2xl font-black text-orange-400">{{ $stats['active'] }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-400">Delayed Alerts</div>
                <div class="text-2xl font-black text-rose-400">{{ $stats['delayed'] }}</div>
            </div>
            <div>
                <div class="text-xs text-slate-400">Delivered</div>
                <div class="text-2xl font-black text-emerald-400">{{ $stats['delivered'] }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Customer Isolated Shipments List -->
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-lg font-black text-slate-900">Your Shipments</h2>
            @forelse($shipments as $shipment)
                <a href="{{ route('shipments.show', $shipment) }}" class="block bg-white rounded-2xl border border-slate-200 hover:border-[#FF5500] p-5 shadow-sm transition group">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3">
                            <span class="font-mono font-black text-lg text-slate-900 group-hover:text-[#FF5500]">{{ $shipment->shipment_number }}</span>
                            @include('partials.status-badge', ['status' => $shipment->current_status])
                        </div>
                        @if($shipment->is_delayed)
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                Delayed +{{ $shipment->delay_hours }}h ({{ $shipment->delay_severity }})
                            </span>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs text-slate-600">
                        <div>
                            <span class="text-slate-400 block">Route</span>
                            <span class="font-bold text-slate-800">{{ $shipment->originLocation->city }} &rarr; {{ $shipment->destinationLocation->city }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Carrier & Cargo</span>
                            <span class="font-semibold">{{ $shipment->carrier->name }} &bull; {{ $shipment->cargo_type }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">ETA</span>
                            <span class="font-semibold">{{ ($shipment->actual_arrival ?? $shipment->estimated_arrival)?->format('M d, Y H:i') ?? 'Pending' }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500">
                    No shipments found matching your account.
                </div>
            @endforelse
            <div class="pt-2">{{ $shipments->links() }}</div>
        </div>

        <!-- In-App Notifications Sidebar -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm h-fit">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-black text-slate-900">Notifications @if($unreadCount > 0)<span class="text-[#FF5500]">({{ $unreadCount }})</span>@endif</h2>
                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('customer.notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="text-xs font-bold text-[#FF5500] hover:underline">Mark all read</button>
                    </form>
                @endif
            </div>
            <div class="space-y-3">
                @forelse($notifications as $notif)
                    <div class="p-3.5 rounded-xl border {{ $notif->is_read ? 'bg-slate-50 border-slate-200' : 'bg-orange-50/70 border-orange-200' }}">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded {{ $notif->type === 'EXCEPTION' || $notif->type === 'DELAY' ? 'bg-rose-100 text-rose-800' : 'bg-slate-200 text-slate-700' }}">{{ $notif->type }}</span>
                            <span class="text-[11px] text-slate-400">{{ $notif->created_at?->diffForHumans() }}</span>
                        </div>
                        <div class="font-bold text-xs text-slate-900">{{ $notif->title }}</div>
                        <p class="text-xs text-slate-600 mt-0.5">{{ $notif->message }}</p>
                        @if(! $notif->is_read)
                            <form method="POST" action="{{ route('customer.notifications.read', $notif) }}" class="mt-2">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-[11px] font-bold text-[#FF5500] hover:underline">Mark read</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-slate-500">You have no notifications yet.</p>
                @endforelse
            </div>
            <a href="{{ route('customer.notifications') }}" class="block text-center mt-4 text-xs font-bold text-slate-600 hover:text-[#FF5500]">
                View Notification Center &rarr;
            </a>
        </div>
    </div>
@endsection
