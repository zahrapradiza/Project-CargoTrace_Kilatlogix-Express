<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperatorController extends Controller
{
    public function dashboard(Request $request): View
    {
        $activeCount = Shipment::whereNotIn('current_status', [Shipment::STATUS_DELIVERED, Shipment::STATUS_CANCELLED])->count();
        $delayedCount = Shipment::where('is_delayed', true)
            ->whereNotIn('current_status', [Shipment::STATUS_DELIVERED, Shipment::STATUS_CANCELLED])
            ->count();
        $openExceptionsCount = ShipmentException::where('status', '!=', 'RESOLVED')->count();
        $deliveredTodayCount = Shipment::where('current_status', Shipment::STATUS_DELIVERED)
            ->whereDate('updated_at', today())
            ->count();

        $followUpShipments = Shipment::with([
            'customer',
            'carrier',
            'originLocation',
            'destinationLocation',
            'exceptions' => fn ($q) => $q->where('status', '!=', 'RESOLVED'),
        ])
            ->where(function ($q) {
                $q->where('is_delayed', true)
                    ->orWhereHas('exceptions', fn ($eq) => $eq->where('status', '!=', 'RESOLVED'))
                    ->orWhereIn('current_status', [Shipment::STATUS_CREATED, Shipment::STATUS_BOOKED, Shipment::STATUS_PICKED_UP]);
            })
            ->whereNotIn('current_status', [Shipment::STATUS_DELIVERED, Shipment::STATUS_CANCELLED])
            ->orderByDesc('is_delayed')
            ->orderBy('expected_arrival')
            ->limit(10)
            ->get();

        $activeShipments = Shipment::whereNotIn('current_status', [Shipment::STATUS_DELIVERED, Shipment::STATUS_CANCELLED])
            ->orderByDesc('id')
            ->get(['id', 'shipment_number', 'current_status']);

        $openExceptions = ShipmentException::with(['shipment', 'reporter'])
            ->where('status', '!=', 'RESOLVED')
            ->orderByDesc('detected_at')
            ->limit(8)
            ->get();

        $locations = Location::orderBy('name')->get();
        $customers = User::where('role', User::ROLE_CUSTOMER)->orderBy('name')->get();
        $carriers = Carrier::where('is_active', true)->orderBy('name')->get();

        return view('operator.dashboard', compact(
            'activeCount',
            'delayedCount',
            'openExceptionsCount',
            'deliveredTodayCount',
            'followUpShipments',
            'activeShipments',
            'openExceptions',
            'locations',
            'customers',
            'carriers'
        ));
    }
}
