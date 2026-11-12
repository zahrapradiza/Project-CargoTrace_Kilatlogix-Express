<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Shipment;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        $baseQuery = Shipment::with(['carrier', 'originLocation', 'destinationLocation', 'trackingEvents.location'])
            ->where('customer_id', $user->id);

        if ($search !== '') {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                    ->orWhere('cargo_type', 'like', "%{$search}%");
            });
        }

        $shipments = $baseQuery->orderByDesc('id')->paginate(8)->withQueryString();

        $stats = [
            'total' => Shipment::where('customer_id', $user->id)->count(),
            'active' => Shipment::where('customer_id', $user->id)
                ->whereNotIn('current_status', [Shipment::STATUS_DELIVERED, Shipment::STATUS_CANCELLED])
                ->count(),
            'delayed' => Shipment::where('customer_id', $user->id)->where('is_delayed', true)->count(),
            'delivered' => Shipment::where('customer_id', $user->id)->where('current_status', Shipment::STATUS_DELIVERED)->count(),
        ];

        $notifications = $user->inAppNotifications()
            ->with('shipment')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $unreadCount = $user->inAppNotifications()->where('is_read', false)->count();

        return view('customer.dashboard', compact('shipments', 'stats', 'notifications', 'unreadCount', 'search'));
    }

    public function notifications(Request $request): View
    {
        $notifications = $request->user()
            ->inAppNotifications()
            ->with('shipment')
            ->orderByDesc('id')
            ->paginate(20);

        return view('customer.notifications', compact('notifications'));
    }

    public function markNotificationRead(Request $request, Notification $notification): RedirectResponse
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $this->notificationService->markAsRead($notification);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return back()->with('success', 'All notifications marked as read.');
    }
}
