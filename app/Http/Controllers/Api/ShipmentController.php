<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreShipmentRequest;
use App\Http\Requests\Api\UpdateShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use App\Services\ExternalIntegrationService;
use App\Services\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShipmentController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService,
        protected ExternalIntegrationService $externalIntegrationService
    ) {
    }

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Shipment::class);

        $user = $request->user();
        $query = Shipment::with(['customer', 'carrier', 'originLocation', 'destinationLocation']);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('current_status', $request->string('status'));
        }

        if ($request->filled('carrier_id')) {
            $query->where('carrier_id', $request->integer('carrier_id'));
        }

        if ($request->filled('origin_location_id')) {
            $query->where('origin_location_id', $request->integer('origin_location_id'));
        }

        if ($request->filled('destination_location_id')) {
            $query->where('destination_location_id', $request->integer('destination_location_id'));
        }

        if ($request->filled('is_delayed')) {
            $query->where('is_delayed', filter_var($request->input('is_delayed'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('shipment_number', 'like', $term)
                    ->orWhere('cargo_type', 'like', $term);
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return ShipmentResource::collection(
            $query->orderByDesc('id')->paginate($perPage)
        );
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        Gate::authorize('create', Shipment::class);

        $shipment = $this->shipmentService->create($request->validated(), $request->user());

        return (new ShipmentResource($shipment))
            ->additional([
                'success' => true,
                'message' => 'Shipment created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Shipment $shipment): ShipmentResource
    {
        Gate::authorize('view', $shipment);

        $shipment->load([
            'customer',
            'carrier',
            'originLocation',
            'destinationLocation',
            'items',
            'trackingEvents.location',
            'trackingEvents.creator',
            'exceptions.reporter',
            'exceptions.resolver',
        ]);

        return new ShipmentResource($shipment);
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): ShipmentResource
    {
        Gate::authorize('update', $shipment);

        $updated = $this->shipmentService->update($shipment, $request->validated(), $request->user());

        return (new ShipmentResource($updated))->additional([
            'success' => true,
            'message' => 'Shipment updated successfully.',
        ]);
    }

    public function destroy(Request $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('delete', $shipment);

        $this->shipmentService->delete($shipment, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Shipment deleted successfully.',
        ]);
    }

    public function weather(Request $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('view', $shipment);

        $latestEvent = $shipment->trackingEvents()->reorder('event_time', 'desc')->orderByDesc('id')->first();
        $lat = $request->filled('lat')
            ? (float) $request->input('lat')
            : (float) ($latestEvent?->latitude ?? $shipment->originLocation?->latitude ?? -6.2088);
        $lng = $request->filled('lng')
            ? (float) $request->input('lng')
            : (float) ($latestEvent?->longitude ?? $shipment->originLocation?->longitude ?? 106.8456);

        return response()->json([
            'success' => true,
            'data' => $this->externalIntegrationService->getWeather($lat, $lng),
        ]);
    }
}
