<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Executive Admin') — CargoTrace KilatLogix Express</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        @keyframes klxPulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 85, 0, 0.7); }
            70% { transform: scale(1.1); box-shadow: 0 0 0 12px rgba(255, 85, 0, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 85, 0, 0); }
        }
        .klx-pulse-marker {
            animation: klxPulse 1.8s infinite;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex">
    <!-- Executive Dark Slate & Indigo Sidebar -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0">
        <div class="p-5 border-b border-slate-800 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center font-black text-white shadow-lg shadow-indigo-600/30">
                KLX
            </div>
            <div>
                <div class="font-bold tracking-tight text-white">CargoTrace</div>
                <div class="text-xs text-indigo-400 font-medium">Executive Admin</div>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>Executive KPIs</span>
            </a>
            <a href="{{ route('shipments.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('shipments.index') || request()->routeIs('shipments.show') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>All Shipments</span>
            </a>
            <a href="{{ route('shipments.create') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('shipments.create') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>+ New Shipment</span>
            </a>

            <div class="pt-4 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Network & Master Data</div>
            <a href="{{ route('admin.carriers') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.carriers') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>Carriers</span>
            </a>
            <a href="{{ route('admin.locations') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.locations') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>Locations & Hubs</span>
            </a>

            <div class="pt-4 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Governance</div>
            <a href="{{ route('admin.users') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.users') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>Users & Roles</span>
            </a>
            <a href="{{ route('admin.audit-logs') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.audit-logs') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>Audit Logs</span>
            </a>
            <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition {{ request()->routeIs('admin.settings') ? 'bg-indigo-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <span>Threshold Settings</span>
            </a>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-900/70">
            <div class="text-xs text-slate-400">Signed in as</div>
            <div class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</div>
            <div class="text-xs text-indigo-400 uppercase font-bold mb-3">{{ auth()->user()->role }}</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full py-2 px-3 rounded-lg bg-slate-800 hover:bg-rose-600/90 text-xs font-semibold text-slate-200 transition">
                    Sign Out
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Executive Workspace -->
    <div class="flex-1 flex flex-col min-w-0 bg-slate-100 text-slate-900">
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-900">@yield('header', 'Executive Dashboard')</h1>
                <p class="text-xs text-slate-500">KilatLogix Express — Global Control Tower</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('public.track') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 px-3 py-1.5 rounded-lg border border-indigo-200 bg-indigo-50">
                    Public Track Portal
                </a>
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">ADMIN MODE</span>
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

