<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Shipment — CargoTrace KilatLogix Express (KLX)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        @keyframes klxPulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 85, 0, 0.75); }
            70% { transform: scale(1.12); box-shadow: 0 0 0 14px rgba(255, 85, 0, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 85, 0, 0); }
        }
        .klx-pulse-marker { animation: klxPulse 1.8s infinite; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col">
    <!-- Top KilatLogix Express Public Header -->
    <header class="bg-[#0F172A] text-white border-b-4 border-[#FF5500]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('public.track') }}" class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg bg-[#FF5500] font-black text-sm tracking-wider shadow">KLX</span>
                <span class="font-bold text-lg">CargoTrace <span class="text-[#FF5500]">KilatLogix Express</span></span>
            </a>
            <div class="flex items-center gap-3">
                @auth
                    @php
                        $dashRoute = match(auth()->user()->role) {
                            'admin' => route('admin.dashboard'),
                            'operator' => route('operator.dashboard'),
                            default => route('customer.dashboard'),
                        };
                    @endphp
                    <a href="{{ $dashRoute }}" class="px-4 py-2 rounded-lg bg-[#FF5500] hover:bg-orange-600 text-xs font-bold transition">
                        Go to My Workspace
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg border border-slate-700 hover:bg-slate-800 text-xs font-semibold transition">
                        Portal Sign In
                    </a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-lg bg-[#FF5500] hover:bg-orange-600 text-xs font-bold transition">
                        Customer Register
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Search Section -->
    <section class="bg-gradient-to-br from-[#0F172A] via-slate-900 to-slate-800 text-white py-14">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
            <span class="inline-block px-3 py-1 rounded-full bg-orange-500/20 text-[#FF5500] text-xs font-bold uppercase tracking-wider mb-3 border border-orange-500/30">
                Global Shipment Visibility Platform
            </span>
            <h1 class="text-3xl sm:text-4xl font-black tracking-tight mb-3">
                Track Your <span class="text-[#FF5500]">KilatLogix Express</span> Cargo in Real Time
            </h1>
            <p class="text-slate-300 text-sm sm:text-base mb-8 max-w-2xl mx-auto">
                Enter your Airwaybill / Shipment Number (e.g. <code class="text-orange-300 font-mono">KLX-2026-0001</code>) to inspect live coordinates, ETA delay evaluation, and checkpoint history.
            </p>

            <form method="GET" action="{{ route('public.track') }}" class="flex flex-col sm:flex-row gap-3 max-w-2xl mx-auto">
                <input type="text" name="awb" value="{{ $awb }}" placeholder="Enter Waybill Number (e.g. KLX-2026-0001)"
                    class="flex-1 px-5 py-3.5 rounded-xl bg-white text-slate-900 font-semibold placeholder-slate-400 focus:outline-none focus:ring-4 focus:ring-orange-500/40 shadow-lg" required>
                <button type="submit" class="px-7 py-3.5 rounded-xl bg-[#FF5500] hover:bg-orange-600 text-white font-black tracking-wide shadow-lg shadow-orange-600/30 transition">
                    TRACK CARGO
                </button>
            </form>

            @if($sampleShipments->isNotEmpty())
                <div class="mt-5 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-400">
                    <span>Quick sample waybills:</span>
                    @foreach($sampleShipments as $sample)
                        <a href="{{ route('public.track', ['awb' => $sample->shipment_number]) }}"
                            class="px-2.5 py-1 rounded-md bg-slate-800 hover:bg-slate-700 text-orange-300 font-mono border border-slate-700 transition">
                            {{ $sample->shipment_number }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <!-- Tracking Result Section -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if($notFound)
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-8 text-center">
                <h2 class="text-lg font-bold text-rose-900 mb-1">Waybill "{{ $awb }}" Not Found</h2>
                <p class="text-sm text-rose-700">Please double-check the shipment number format (for example, <span class="font-mono font-bold">KLX-2026-0001</span>) and try again.</p>
            </div>
        @elseif($shipment)
            @include('partials.shipment-tracker-map', [
                'shipment' => $shipment,
                'weatherUrl' => route('public.track.weather'),
            ])

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Route & Carrier Summary -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h3 class="font-bold text-slate-900 border-b border-slate-100 pb-3">Shipment Summary</h3>
                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-slate-400 font-semibold">Origin</div>
                            <div class="font-bold text-slate-800">{{ $shipment->originLocation->name }}</div>
                            <div class="text-xs text-slate-500">{{ $shipment->originLocation->city }}, {{ $shipment->originLocation->country }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 font-semibold">Destination</div>
                            <div class="font-bold text-slate-800">{{ $shipment->destinationLocation->name }}</div>
                            <div class="text-xs text-slate-500">{{ $shipment->destinationLocation->city }}, {{ $shipment->destinationLocation->country }}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                            <div>
                                <div class="text-xs text-slate-400">Carrier</div>
                                <div class="font-semibold text-slate-800">{{ $shipment->carrier->name }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400">Mode</div>
                                <div class="font-semibold text-slate-800">{{ $shipment->carrier->transport_mode }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400">Expected Arrival</div>
                                <div class="font-semibold text-slate-800">{{ $shipment->expected_arrival?->format('M d, Y H:i') }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-slate-400">Estimated Arrival</div>
                                <div class="font-semibold {{ $shipment->is_delayed ? 'text-rose-600' : 'text-emerald-600' }}">
                                    {{ ($shipment->actual_arrival ?? $shipment->estimated_arrival)?->format('M d, Y H:i') ?? 'TBD' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chronological Tracking Timeline -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="font-bold text-slate-900 border-b border-slate-100 pb-3 mb-4">Chronological Tracking History</h3>
                    <div class="space-y-4">
                        @forelse($shipment->trackingEvents->sortByDesc('event_time') as $event)
                            <div class="flex gap-4 p-4 rounded-xl border {{ $event->is_exception ? 'bg-rose-50 border-rose-200' : 'bg-slate-50 border-slate-200' }}">
                                <div class="shrink-0 mt-1">
                                    <span class="w-3 h-3 rounded-full block {{ $event->is_exception ? 'bg-rose-600' : 'bg-[#FF5500]' }}"></span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                                        <div class="flex items-center gap-2">
                                            @include('partials.status-badge', ['status' => $event->status])
                                            <span class="text-xs font-bold text-slate-700">{{ $event->location?->name ?? 'Transit Coordinate' }}</span>
                                        </div>
                                        <span class="text-xs font-mono text-slate-500">{{ $event->event_time?->format('M d, Y H:i') }}</span>
                                    </div>
                                    <p class="text-sm {{ $event->is_exception ? 'text-rose-800 font-semibold' : 'text-slate-700' }}">{{ $event->description }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No tracking checkpoints recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-[#FF5500] flex items-center justify-center font-black mb-3">01</div>
                    <h3 class="font-bold text-slate-900 mb-1">Real-Time Map Visibility</h3>
                    <p class="text-sm text-slate-600">Interactive OpenStreetMap route polylines showing origin, checkpoints, exceptions, and live cargo coordinates.</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center font-black mb-3">02</div>
                    <h3 class="font-bold text-slate-900 mb-1">Deterministic ETA & Delay SLA</h3>
                    <p class="text-sm text-slate-600">Automated delay hour calculations and severity alerts (LOW, MEDIUM, HIGH) against expected schedules.</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black mb-3">03</div>
                    <h3 class="font-bold text-slate-900 mb-1">Live Weather Telemetry</h3>
                    <p class="text-sm text-slate-600">Open-Meteo weather integration at every waypoint to anticipate maritime, air, and road weather disruptions.</p>
                </div>
            </div>
        @endif
    </main>

    <footer class="bg-[#0F172A] text-slate-400 text-xs py-5 border-t border-slate-800 text-center">
        &copy; {{ date('Y') }} CargoTrace — KilatLogix Express (KLX). Global Shipment Visibility Platform.
    </footer>

    @stack('scripts')
</body>
</html>
