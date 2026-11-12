<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentException;
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

        $openExceptions = ShipmentException::with(['shipment', 'reporter'])
            ->where('status', '!=', 'RESOLVED')
            ->orderByDesc('detected_at')
            ->limit(8)
            ->get();

        return view('operator.dashboard', compact(
            'activeCount',
            'delayedCount',
            'openExceptionsCount',
            'deliveredTodayCount',
            'followUpShipments',
            'openExceptions'
        ));
    }
}
