<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Services\ExternalIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicTrackingController extends Controller
{
    public function __construct(protected ExternalIntegrationService $externalIntegrationService)
    {
    }

    public function index(Request $request): View
    {
        $awb = trim((string) $request->query('awb', ''));
        $shipment = null;
        $notFound = false;

        if ($awb !== '') {
            $shipment = Shipment::with([
                'carrier',
                'originLocation',
                'destinationLocation',
                'trackingEvents.location',
                'exceptions' => fn ($q) => $q->where('status', '!=', 'RESOLVED'),
            ])->where('shipment_number', $awb)->first();

            if (! $shipment) {
                $notFound = true;
            }
        }

        $sampleShipments = Shipment::select('shipment_number', 'current_status', 'is_delayed')
            ->orderBy('id')
            ->limit(4)
            ->get();

        return view('public.track', compact('awb', 'shipment', 'notFound', 'sampleShipments'));
    }

    public function weather(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->externalIntegrationService->getWeather((float) $data['lat'], (float) $data['lng']),
        ]);
    }
}

