<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Customer Portal') — KilatLogix Express (KLX)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        @keyframes klxPulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 85, 0, 0.75); }
            70% { transform: scale(1.12); box-shadow: 0 0 0 14px rgba(255, 85, 0, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 85, 0, 0); }
        }
        .klx-pulse-marker {
            animation: klxPulse 1.8s infinite;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col">
    <!-- KilatLogix Express Vibrant Orange & Deep Navy Consumer Navbar -->
    <nav class="bg-[#0F172A] text-white border-b-4 border-[#FF5500] sticky top-0 z-40 shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('customer.dashboard') }}" class="flex items-center gap-2.5">
                    <span class="px-2.5 py-1 rounded-lg bg-[#FF5500] text-white font-black tracking-wider text-sm shadow">KLX</span>
                    <span class="font-bold text-lg tracking-tight">KilatLogix <span class="text-[#FF5500]">Express</span></span>
                </a>
                <div class="hidden md:flex items-center gap-2 text-sm">
                    <a href="{{ route('customer.dashboard') }}" class="px-3 py-2 rounded-lg transition {{ request()->routeIs('customer.dashboard') ? 'bg-white/10 text-[#FF5500] font-semibold' : 'text-slate-300 hover:text-white' }}">
                        My Shipments
                    </a>
                    <a href="{{ route('shipments.index') }}" class="px-3 py-2 rounded-lg transition {{ request()->routeIs('shipments.*') ? 'bg-white/10 text-[#FF5500] font-semibold' : 'text-slate-300 hover:text-white' }}">
                        Waybill List
                    </a>
                    <a href="{{ route('customer.notifications') }}" class="px-3 py-2 rounded-lg transition {{ request()->routeIs('customer.notifications') ? 'bg-white/10 text-[#FF5500] font-semibold' : 'text-slate-300 hover:text-white' }}">
                        Notifications
                        @php $unread = auth()->user()->inAppNotifications()->where('is_read', false)->count(); @endphp
                        @if($unread > 0)
                            <span class="ml-1 px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-[#FF5500] text-white">{{ $unread }}</span>
                        @endif
                    </a>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <div class="text-xs font-semibold text-white">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-slate-400">{{ auth()->user()->company_name ?: 'Valued Customer' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-[#FF5500] hover:bg-orange-600 text-xs font-bold text-white transition">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="bg-[#0F172A] text-slate-400 text-xs py-5 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-2">
            <span>&copy; {{ date('Y') }} CargoTrace — KilatLogix Express (KLX). Global Shipment Visibility Platform.</span>
            <span>Map data &copy; OpenStreetMap contributors &bull; Weather by Open-Meteo</span>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

