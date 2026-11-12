@extends('layouts.admin')

@section('title', 'System Settings')
@section('header', 'Delay Threshold & External API Configuration')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-4">Deterministic ETA & Delay Rules</h3>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <dt class="text-slate-500">Shipment Prefix</dt>
                    <dd class="font-mono font-bold">{{ $config['shipment_prefix'] }}</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <dt class="text-slate-500">SLA Delay Threshold (.env)</dt>
                    <dd class="font-mono font-bold text-indigo-700">{{ $config['delay_threshold_hours'] }} hours</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <dt class="text-slate-500">LOW Delay Severity</dt>
                    <dd class="font-semibold">1 – {{ $config['delay_severity_thresholds']['low_max_hours'] }} hours</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <dt class="text-slate-500">MEDIUM Delay Severity</dt>
                    <dd class="font-semibold">&gt; {{ $config['delay_severity_thresholds']['low_max_hours'] }} – {{ $config['delay_severity_thresholds']['medium_max_hours'] }} hours</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-slate-500">HIGH Delay Severity</dt>
                    <dd class="font-semibold text-rose-600">&gt; {{ $config['delay_severity_thresholds']['medium_max_hours'] }} hours</dd>
                </div>
            </dl>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-4">External Integration Endpoints</h3>
            <dl class="space-y-3 text-sm">
                <div class="py-2 border-b border-slate-100">
                    <dt class="text-xs text-slate-500">Open-Meteo Weather API</dt>
                    <dd class="font-mono text-xs text-slate-800 mt-0.5">{{ $config['external_apis']['open_meteo_url'] }}</dd>
                </div>
                <div class="py-2 border-b border-slate-100">
                    <dt class="text-xs text-slate-500">Nominatim OpenStreetMap Geocoder</dt>
                    <dd class="font-mono text-xs text-slate-800 mt-0.5">{{ $config['external_apis']['nominatim_url'] }}</dd>
                </div>
                <div class="py-2">
                    <dt class="text-xs text-slate-500">HTTP Client Timeout</dt>
                    <dd class="font-mono font-bold text-indigo-700 mt-0.5">{{ $config['external_apis']['timeout_seconds'] }} seconds (with offline fallback)</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
