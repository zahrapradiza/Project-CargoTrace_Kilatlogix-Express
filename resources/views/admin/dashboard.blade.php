@extends('layouts.admin')

@section('title', 'Executive KPI Dashboard')
@section('header', 'Executive Control Tower')

@section('content')
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-bold uppercase text-slate-400">Total Shipments</div>
            <div class="text-3xl font-black text-slate-900 mt-1">{{ $totalShipments }}</div>
            <div class="text-xs text-slate-500 mt-1">All global waybills</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-bold uppercase text-slate-400">In Transit</div>
            <div class="text-3xl font-black text-indigo-600 mt-1">{{ $inTransitCount }}</div>
            <div class="text-xs text-slate-500 mt-1">Active ocean/air/road</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-bold uppercase text-slate-400">Delivered</div>
            <div class="text-3xl font-black text-emerald-600 mt-1">{{ $deliveredCount }}</div>
            <div class="text-xs text-slate-500 mt-1">Completed SLA</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-bold uppercase text-slate-400">Delayed Shipments</div>
            <div class="text-3xl font-black text-rose-600 mt-1">{{ $delayedCount }}</div>
            <div class="text-xs text-slate-500 mt-1">
                Delay Rate: {{ $totalShipments > 0 ? round(($delayedCount / $totalShipments) * 100, 1) : 0 }}% &bull; On-Time: {{ $onTimeRate }}%
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="text-xs font-bold uppercase text-slate-400">Open Exceptions</div>
            <div class="text-3xl font-black text-amber-600 mt-1">{{ $openExceptionsCount }}</div>
            <div class="text-xs text-slate-500 mt-1">Requires intervention</div>
        </div>
    </div>

    <!-- Chart.js Analytics Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-1">Shipments by Status</h3>
            <p class="text-xs text-slate-500 mb-4">Lifecycle distribution across platform</p>
            <div class="h-64 flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-1">Carrier Volume vs Delays</h3>
            <p class="text-xs text-slate-500 mb-4">Performance comparison by carrier</p>
            <div class="h-64">
                <canvas id="carrierChart"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="font-bold text-slate-900 mb-1">Exceptions Breakdown</h3>
            <p class="text-xs text-slate-500 mb-4">Root-cause exception distribution</p>
            <div class="h-64">
                <canvas id="exceptionChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Delayed Watchlist & Recent Audit Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900">Top Delayed Shipments</h3>
                <a href="{{ route('shipments.index', ['delayed' => 1]) }}" class="text-xs font-semibold text-indigo-600 hover:underline">View All &rarr;</a>
            </div>
            <div class="space-y-3">
                @forelse($delayedShipments as $ship)
                    <a href="{{ route('shipments.show', $ship) }}" class="block p-3.5 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/40 transition">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-sm text-slate-900">{{ $ship->shipment_number }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">+{{ $ship->delay_hours }}h ({{ $ship->delay_severity }})</span>
                        </div>
                        <div class="text-xs text-slate-600 mt-1">
                            {{ $ship->originLocation->city }} &rarr; {{ $ship->destinationLocation->city }} &bull; {{ $ship->carrier->name }}
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">All active shipments are currently on schedule.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-900">Recent Audit Trail</h3>
                <a href="{{ route('admin.audit-logs') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Full Audit Log &rarr;</a>
            </div>
            <div class="space-y-2.5 text-xs">
                @forelse($recentAuditLogs as $log)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <div>
                            <span class="font-bold text-indigo-700">{{ $log->action }}</span>
                            <span class="text-slate-500">on {{ class_basename($log->entity_type) }} #{{ $log->entity_id }}</span>
                            <div class="text-slate-400 mt-0.5">Actor: {{ $log->user?->name ?? 'System' }}</div>
                        </div>
                        <div class="font-mono text-slate-400">{{ $log->created_at?->diffForHumans() }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No audit entries yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const statusData = @json($statusBreakdown);
    const carrierLabels = @json($carrierPerformance->pluck('name'));
    const carrierTotals = @json($carrierPerformance->pluck('shipments_count'));
    const carrierDelays = @json($carrierPerformance->pluck('delayed_shipments_count'));
    const exceptionData = @json($exceptionBreakdown);

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: ['#94a3b8', '#0284c7', '#4f46e5', '#f59e0b', '#10b981', '#ef4444'],
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('carrierChart'), {
        type: 'bar',
        data: {
            labels: carrierLabels,
            datasets: [
                { label: 'Total Shipments', data: carrierTotals, backgroundColor: '#4f46e5', borderRadius: 6 },
                { label: 'Delayed', data: carrierDelays, backgroundColor: '#f43f5e', borderRadius: 6 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    new Chart(document.getElementById('exceptionChart'), {
        type: 'bar',
        data: {
            labels: Object.keys(exceptionData),
            datasets: [{
                label: 'Count',
                data: Object.values(exceptionData),
                backgroundColor: '#f59e0b',
                borderRadius: 6
            }]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
});
</script>
@endpush

