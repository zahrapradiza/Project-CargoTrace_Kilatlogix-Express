<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTrackingEventRequest;
use App\Http\Resources\TrackingEventResource;
use App\Models\Shipment;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TrackingController extends Controller
{
    public function __construct(protected TrackingService $trackingService)
    {
    }

    public function index(Request $request, Shipment $shipment)
    {
        Gate::authorize('view', $shipment);

        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);

        return TrackingEventResource::collection(
            $shipment->trackingEvents()->with(['location', 'creator'])->paginate($perPage)
        );
    }

    public function store(StoreTrackingEventRequest $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('addTrackingEvent', $shipment);

        $event = $this->trackingService->addEvent($shipment, $request->validated(), $request->user());

        return (new TrackingEventResource($event))
            ->additional([
                'success' => true,
                'message' => 'Tracking event recorded successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }
}

