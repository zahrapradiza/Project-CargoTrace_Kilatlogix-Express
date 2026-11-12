@extends('layouts.customer')

@section('title', 'Notifications')

@section('content')
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
            <div>
                <h1 class="text-xl font-black text-slate-900">In-App Notification Center</h1>
                <p class="text-xs text-slate-500">Delay warnings, exception alerts, and delivery confirmations for your shipments.</p>
            </div>
            <form method="POST" action="{{ route('customer.notifications.read-all') }}">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl bg-[#FF5500] text-white text-xs font-bold hover:bg-orange-600 transition">
                    Mark All Read
                </button>
            </form>
        </div>

        <div class="space-y-3">
            @forelse($notifications as $notif)
                <div class="p-4 rounded-xl border {{ $notif->is_read ? 'bg-slate-50 border-slate-200' : 'bg-orange-50 border-orange-200' }} flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ in_array($notif->type, ['DELAY', 'EXCEPTION']) ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">{{ $notif->type }}</span>
                            <span class="font-bold text-sm text-slate-900">{{ $notif->title }}</span>
                            <span class="text-xs text-slate-400">{{ $notif->created_at?->format('M d, Y H:i') }}</span>
                        </div>
                        <p class="text-sm text-slate-600">{{ $notif->message }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if($notif->shipment)
                            <a href="{{ route('shipments.show', $notif->shipment) }}" class="text-xs font-bold text-[#0F172A] hover:underline">View Waybill</a>
                        @endif
                        @if(! $notif->is_read)
                            <form method="POST" action="{{ route('customer.notifications.read', $notif) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-white border border-orange-300 text-[#FF5500] text-xs font-bold">Mark Read</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500 text-center py-8">No notifications recorded.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $notifications->links() }}</div>
    </div>
@endsection
