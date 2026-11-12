@extends('layouts.admin')

@section('title', 'Locations & Hubs')
@section('header', 'Global Locations, Ports & Hubs')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm h-fit">
            <h3 class="font-bold text-slate-900 mb-3">Add Global Hub / Port</h3>

            <!-- Nominatim Geocoding Lookup -->
            <div class="mb-4 p-3 rounded-xl bg-indigo-50 border border-indigo-200">
                <label class="block text-[11px] font-bold text-indigo-900 mb-1">Auto-Geocode via Nominatim OSM</label>
                <div class="flex gap-2">
                    <input type="text" id="geocode-query" placeholder="e.g. Port of Singapore" class="flex-1 px-2.5 py-1.5 rounded-lg border border-indigo-200 text-xs bg-white">
                    <button type="button" onclick="lookupGeocode()" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-bold">Lookup</button>
                </div>
                <div id="geocode-status" class="text-[11px] text-indigo-700 mt-1"></div>
            </div>

            <form method="POST" action="{{ route('admin.locations.store') }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Location Code</label>
                    <input type="text" name="code" placeholder="e.g. ID-JKT-HUB" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Facility Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">City</label>
                        <input type="text" name="city" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Country</label>
                        <input type="text" name="country" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Type</label>
                    <select name="type" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        <option value="HUB">HUB</option>
                        <option value="PORT">PORT</option>
                        <option value="AIRPORT">AIRPORT</option>
                        <option value="WAREHOUSE">WAREHOUSE</option>
                        <option value="CUSTOMER_ADDR">CUSTOMER_ADDR</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Latitude</label>
                        <input type="number" step="0.0000001" id="loc-lat" name="latitude" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 mb-1">Longitude</label>
                        <input type="number" step="0.0000001" id="loc-lng" name="longitude" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                    </div>
                </div>
                <button type="submit" class="w-full py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold">Save Location</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-4">Registered Network Locations</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-3">Code</th>
                            <th class="py-3 px-3">Facility</th>
                            <th class="py-3 px-3">Type</th>
                            <th class="py-3 px-3">Coordinates</th>
                            <th class="py-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($locations as $loc)
                            <tr>
                                <td class="py-3 px-3 font-mono font-bold text-indigo-700">{{ $loc->code }}</td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900">{{ $loc->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $loc->city }}, {{ $loc->country }}</div>
                                </td>
                                <td class="py-3 px-3 text-xs font-bold">{{ $loc->type }}</td>
                                <td class="py-3 px-3 font-mono text-xs text-slate-600">{{ $loc->latitude }}, {{ $loc->longitude }}</td>
                                <td class="py-3 px-3 text-right">
                                    <form method="POST" action="{{ route('admin.locations.destroy', $loc) }}" onsubmit="return confirm('Delete location?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $locations->links() }}</div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
async function lookupGeocode() {
    const q = document.getElementById('geocode-query').value.trim();
    const status = document.getElementById('geocode-status');
    if (!q) return;
    status.textContent = 'Querying Nominatim...';
    try {
        const res = await fetch(`{{ route('admin.locations.geocode') }}?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        if (data.available && data.results && data.results.length > 0) {
            document.getElementById('loc-lat').value = data.results[0].latitude;
            document.getElementById('loc-lng').value = data.results[0].longitude;
            status.textContent = `Found: ${data.results[0].display_name}`;
        } else {
            status.textContent = data.message || 'No coordinates found (offline fallback).';
        }
    } catch (e) {
        status.textContent = 'Geocoding service unreachable.';
    }
}
</script>
@endpush
