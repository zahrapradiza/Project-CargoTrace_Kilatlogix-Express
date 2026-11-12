@extends($layout)

@section('title', 'Create Shipment')
@section('header', 'Register New Shipment Waybill')

@section('content')
    <form method="POST" action="{{ route('shipments.store') }}" class="bg-white rounded-2xl border border-slate-200 p-8 shadow-sm max-w-4xl">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm mb-6">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Customer</label>
                <select name="customer_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                            {{ $customer->name }} ({{ $customer->company_name ?: $customer->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Carrier</label>
                <select name="carrier_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    @foreach($carriers as $carrier)
                        <option value="{{ $carrier->id }}" @selected(old('carrier_id') == $carrier->id)>
                            {{ $carrier->name }} [{{ $carrier->transport_mode }}]
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Origin Facility</label>
                <select name="origin_location_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(old('origin_location_id') == $loc->id)>
                            {{ $loc->code }} — {{ $loc->name }} ({{ $loc->city }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Destination Facility</label>
                <select name="destination_location_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(old('destination_location_id') == $loc->id)>
                            {{ $loc->code }} — {{ $loc->name }} ({{ $loc->city }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Cargo Type</label>
                <input type="text" name="cargo_type" value="{{ old('cargo_type', 'Electronics & Industrial Goods') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Weight (kg)</label>
                    <input type="number" step="0.01" name="weight_kg" value="{{ old('weight_kg', 250) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Volume (CBM)</label>
                    <input type="number" step="0.001" name="volume_cbm" value="{{ old('volume_cbm', 1.5) }}" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Expected Departure</label>
                <input type="datetime-local" name="expected_departure" value="{{ old('expected_departure', now()->format('Y-m-d\TH:i')) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Expected Arrival (SLA)</label>
                <input type="datetime-local" name="expected_arrival" value="{{ old('expected_arrival', now()->addDays(5)->format('Y-m-d\TH:i')) }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
            </div>
        </div>

        <!-- Initial Manifest Item -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 mb-6">
            <div class="text-xs font-bold uppercase text-slate-500 mb-3">Initial Cargo Item Manifest</div>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-sm">
                <input type="text" name="items[0][item_name]" value="{{ old('items.0.item_name', 'Server Rack Unit') }}" placeholder="Item Name" required class="px-3 py-2 rounded-lg border border-slate-300 bg-white">
                <input type="text" name="items[0][sku]" value="{{ old('items.0.sku', 'KLX-SKU-1001') }}" placeholder="SKU" required class="px-3 py-2 rounded-lg border border-slate-300 bg-white">
                <input type="number" name="items[0][quantity]" value="{{ old('items.0.quantity', 2) }}" min="1" placeholder="Qty" required class="px-3 py-2 rounded-lg border border-slate-300 bg-white">
                <input type="number" step="0.01" name="items[0][weight_kg]" value="{{ old('items.0.weight_kg', 125) }}" placeholder="Item Weight (kg)" required class="px-3 py-2 rounded-lg border border-slate-300 bg-white">
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('shipments.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700">Cancel</a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#FF5500] hover:bg-orange-600 text-white text-sm font-bold shadow-md transition">
                Generate Waybill &amp; Register
            </button>
        </div>
    </form>
@endsection

