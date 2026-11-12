<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hub Operator') — CargoTrace KilatLogix Express</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        @keyframes klxPulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(13, 148, 136, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 12px rgba(13, 148, 136, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(13, 148, 136, 0); }
        }
        .klx-pulse-marker {
            animation: klxPulse 1.8s infinite;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex">
    <!-- High-Contrast Teal/Blue Hub Operator Sidebar -->
    <aside class="w-64 bg-gradient-to-b from-teal-900 via-teal-950 to-slate-950 text-white flex flex-col shrink-0">
        <div class="p-5 border-b border-teal-800/60 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-teal-500 flex items-center justify-center font-black text-slate-950 shadow-lg">
                HUB
            </div>
            <div>
                <div class="font-bold tracking-tight">KilatLogix Ops</div>
                <div class="text-xs text-teal-300 font-medium">Drop Point & Hub Desk</div>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1.5 text-sm">
            <a href="{{ route('operator.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('operator.dashboard') ? 'bg-teal-500 text-slate-950 font-bold' : 'text-teal-100 hover:bg-teal-800/60' }}">
                <span>Hub Dispatch Queue</span>
            </a>
            <a href="{{ route('shipments.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('shipments.index') || request()->routeIs('shipments.show') ? 'bg-teal-500 text-slate-950 font-bold' : 'text-teal-100 hover:bg-teal-800/60' }}">
                <span>All Active Shipments</span>
            </a>
            <a href="{{ route('shipments.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('shipments.create') ? 'bg-teal-500 text-slate-950 font-bold' : 'text-teal-100 hover:bg-teal-800/60' }}">
                <span>+ Register Waybill (AWB)</span>
            </a>
        </nav>

        <div class="p-4 border-t border-teal-800/60">
            <div class="text-xs text-teal-300">Hub Officer</div>
            <div class="text-sm font-semibold truncate">{{ auth()->user()->name }}</div>
            <div class="text-xs text-sky-300 uppercase font-bold mb-3">{{ auth()->user()->role }}</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full py-2 px-3 rounded-lg bg-teal-900/80 hover:bg-rose-600 text-xs font-semibold text-white transition">
                    End Shift & Sign Out
                </button>
            </form>
        </div>
    </aside>

    <!-- Operational Hub Main Area -->
    <div class="flex-1 flex flex-col min-w-0">
        <header class="bg-white border-b border-teal-200 px-8 py-4 flex items-center justify-between shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-slate-900">@yield('header', 'Hub Operations Workspace')</h1>
                <p class="text-xs text-teal-700 font-medium">Real-time Waybill Scanning, Checkpoint Logging & Exception Resolution</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('shipments.create') }}" class="px-4 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-sm transition">
                    + Create Shipment
                </a>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-teal-100 text-teal-800 border border-teal-300">OPERATOR</span>
            </div>
        </header>

        <main class="flex-1 p-8 overflow-y-auto">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
