@php
    $statusColors = [
        'CREATED' => 'bg-slate-100 text-slate-700 border-slate-200',
        'BOOKED' => 'bg-sky-100 text-sky-800 border-sky-200',
        'PICKED_UP' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'IN_TRANSIT' => 'bg-amber-100 text-amber-800 border-amber-200',
        'DELIVERED' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'CANCELLED' => 'bg-rose-100 text-rose-800 border-rose-200',
        'EXCEPTION' => 'bg-red-100 text-red-800 border-red-300',
    ];
    $badgeClass = $statusColors[$status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
@endphp
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeClass }}">
    {{ str_replace('_', ' ', $status) }}
</span>
