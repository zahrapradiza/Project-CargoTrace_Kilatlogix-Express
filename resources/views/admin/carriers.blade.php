@extends('layouts.admin')

@section('title', 'Carriers')
@section('header', 'Carrier Fleet Management')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm h-fit">
            <h3 class="font-bold text-slate-900 mb-4">Add New Carrier</h3>
            <form method="POST" action="{{ route('admin.carriers.store') }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Carrier Code</label>
                    <input type="text" name="code" placeholder="e.g. KLX-SEA" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Carrier Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Transport Mode</label>
                    <select name="transport_mode" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                        <option value="AIR">AIR</option>
                        <option value="SEA">SEA</option>
                        <option value="ROAD">ROAD</option>
                        <option value="RAIL">RAIL</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Contact Email</label>
                    <input type="email" name="contact_email" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1">Phone</label>
                    <input type="text" name="phone" class="w-full px-3 py-2 rounded-lg border border-slate-300">
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-indigo-600">
                    Active Carrier
                </label>
                <button type="submit" class="w-full py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold">Add Carrier</button>
            </form>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-4">Registered Carriers</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-3">Code</th>
                            <th class="py-3 px-3">Name</th>
                            <th class="py-3 px-3">Mode</th>
                            <th class="py-3 px-3">Shipments</th>
                            <th class="py-3 px-3">Status</th>
                            <th class="py-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($carriers as $carrier)
                            <tr>
                                <td class="py-3 px-3 font-mono font-bold text-indigo-700">{{ $carrier->code }}</td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900">{{ $carrier->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $carrier->contact_email }}</div>
                                </td>
                                <td class="py-3 px-3 text-xs font-bold">{{ $carrier->transport_mode }}</td>
                                <td class="py-3 px-3 font-semibold">{{ $carrier->shipments_count }}</td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $carrier->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $carrier->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <form method="POST" action="{{ route('admin.carriers.destroy', $carrier) }}" onsubmit="return confirm('Delete carrier?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-rose-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $carriers->links() }}</div>
        </div>
    </div>
@endsection
