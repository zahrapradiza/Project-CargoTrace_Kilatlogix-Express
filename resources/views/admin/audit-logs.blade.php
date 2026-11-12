@extends('layouts.admin')

@section('title', 'Audit Logs')
@section('header', 'System Governance & Audit Logs')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <form method="GET" class="flex flex-wrap gap-2 mb-5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search action or entity..." class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-sm">
            <select name="action" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
                <option value="">All Actions</option>
                @foreach($actions as $act)
                    <option value="{{ $act }}" @selected(request('action') === $act)>{{ $act }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-bold">Filter</button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 uppercase text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-3">Timestamp</th>
                        <th class="py-3 px-3">Actor</th>
                        <th class="py-3 px-3">Action</th>
                        <th class="py-3 px-3">Entity</th>
                        <th class="py-3 px-3">Old / New Values</th>
                        <th class="py-3 px-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($logs as $log)
                        <tr class="align-top">
                            <td class="py-3 px-3 font-mono text-slate-500 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="py-3 px-3 font-semibold text-slate-800">{{ $log->user?->name ?? 'System' }}</td>
                            <td class="py-3 px-3"><span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold">{{ $log->action }}</span></td>
                            <td class="py-3 px-3 font-mono">{{ class_basename($log->entity_type) }} #{{ $log->entity_id }}</td>
                            <td class="py-3 px-3 max-w-md">
                                @if($log->old_values)
                                    <div class="text-rose-700 font-mono truncate">Old: {{ json_encode($log->old_values) }}</div>
                                @endif
                                @if($log->new_values)
                                    <div class="text-emerald-700 font-mono truncate">New: {{ json_encode($log->new_values) }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-400">{{ $log->ip_address }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $logs->links() }}</div>
    </div>
@endsection

