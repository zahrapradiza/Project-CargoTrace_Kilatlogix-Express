<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use App\Services\ExceptionService;
use App\Services\ExternalIntegrationService;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShipmentWebController extends Controller
{
    public function __construct(
        protected ShipmentService $shipmentService,
        protected TrackingService $trackingService,
        protected ExceptionService $exceptionService,
        protected ExternalIntegrationService $externalIntegrationService
    ) {
    }

    public function index(Request $request): View
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

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        if ($request->filled('delayed')) {
            if ($request->string('delayed')->toString() === '1') {
                $query->where('is_delayed', true);
            } elseif ($request->string('delayed')->toString() === '0') {
                $query->where('is_delayed', false);
            }
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('shipment_number', 'like', $term)
                    ->orWhere('cargo_type', 'like', $term);
            });
        }

        match ($request->string('sort')->toString()) {
            'oldest' => $query->orderBy('id'),
            'eta_asc' => $query->orderBy('expected_arrival'),
            'delay_desc' => $query->orderByDesc('delay_hours')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };

        $shipments = $query->paginate(12)->withQueryString();
        $carriers = Carrier::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $layout = $this->resolveLayout($user);

        return view('shipments.index', compact('shipments', 'carriers', 'locations', 'layout'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Shipment::class);

        $customers = User::where('role', User::ROLE_CUSTOMER)->orderBy('name')->get();
        $carriers = Carrier::where('is_active', true)->orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $layout = $this->resolveLayout($request->user());

        return view('shipments.create', compact('customers', 'carriers', 'locations', 'layout'));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Shipment::class);

        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:users,id'],
            'carrier_id' => ['required', 'integer', 'exists:carriers,id'],
            'origin_location_id' => ['required', 'integer', 'exists:locations,id'],
            'destination_location_id' => ['required', 'integer', 'exists:locations,id', 'different:origin_location_id'],
            'cargo_type' => ['required', 'string', 'max:100'],
            'weight_kg' => ['required', 'numeric', 'min:0.01'],
            'volume_cbm' => ['nullable', 'numeric', 'min:0'],
            'expected_departure' => ['required', 'date'],
            'expected_arrival' => ['required', 'date', 'after_or_equal:expected_departure'],
            'estimated_arrival' => ['nullable', 'date'],
            'delay_reason' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['required_with:items', 'string', 'max:255'],
            'items.*.sku' => ['required_with:items', 'string', 'max:80'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.weight_kg' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.declared_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $shipment = $this->shipmentService->create($data, $request->user());

        return redirect()
            ->route('shipments.show', $shipment)
            ->with('success', "Shipment {$shipment->shipment_number} created successfully.");
    }

    public function show(Request $request, Shipment $shipment): View
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

        $locations = Location::orderBy('name')->get();
        $layout = $this->resolveLayout($request->user());

        $latestEvent = $shipment->trackingEvents->last();
        $latestLat = $latestEvent?->latitude ?? $shipment->originLocation?->latitude;
        $latestLng = $latestEvent?->longitude ?? $shipment->originLocation?->longitude;

        return view('shipments.show', compact('shipment', 'locations', 'layout', 'latestLat', 'latestLng'));
    }

    public function update(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('update', $shipment);

        $data = $request->validate([
            'carrier_id' => ['sometimes', 'integer', 'exists:carriers,id'],
            'cargo_type' => ['sometimes', 'string', 'max:100'],
            'expected_arrival' => ['sometimes', 'date'],
            'estimated_arrival' => ['nullable', 'date'],
            'current_status' => ['sometimes', Rule::in(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED', 'CANCELLED'])],
            'delay_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->shipmentService->update($shipment, $data, $request->user());

        return back()->with('success', "Shipment {$shipment->shipment_number} updated.");
    }

    public function destroy(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('delete', $shipment);

        $number = $shipment->shipment_number;
        $this->shipmentService->delete($shipment, $request->user());

        return redirect()->route('shipments.index')->with('success', "Shipment {$number} archived.");
    }

    public function addTrackingEvent(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('addTrackingEvent', $shipment);

        $data = $request->validate([
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'status' => ['required', Rule::in(['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'EXCEPTION', 'DELIVERED'])],
            'event_time' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'description' => ['required', 'string', 'max:1000'],
            'estimated_arrival' => ['nullable', 'date'],
            'delay_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->trackingService->addEvent($shipment, $data, $request->user());

        return back()->with('success', 'Tracking event appended successfully.');
    }

    public function openException(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('manageException', $shipment);

        $data = $request->validate([
            'exception_type' => ['required', Rule::in([
                'CUSTOMS_HOLD',
                'WEATHER_DELAY',
                'PORT_CONGESTION',
                'DAMAGED',
                'MISROUTED',
                'DOCUMENTATION_ISSUE',
            ])],
            'severity' => ['required', Rule::in(['LOW', 'MEDIUM', 'HIGH'])],
            'description' => ['required', 'string', 'max:1000'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $this->exceptionService->openException($shipment, $data, $request->user());

        return back()->with('success', 'Shipment exception reported.');
    }

    public function updateException(Request $request, ShipmentException $exception): RedirectResponse
    {
        Gate::authorize('manageException', $exception->shipment);

        $data = $request->validate([
            'status' => ['required', Rule::in(['OPEN', 'INVESTIGATING', 'RESOLVED'])],
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->exceptionService->updateException($exception, $data, $request->user());

        return back()->with('success', 'Exception status updated.');
    }

    public function weather(Request $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('view', $shipment);

        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->externalIntegrationService->getWeather((float) $data['lat'], (float) $data['lng']),
        ]);
    }

    protected function resolveLayout(User $user): string
    {
        return match ($user->role) {
            User::ROLE_ADMIN => 'layouts.admin',
            User::ROLE_OPERATOR => 'layouts.operator',
            default => 'layouts.customer',
        };
    }
}
