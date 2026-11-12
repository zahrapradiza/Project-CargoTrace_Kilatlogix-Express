<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCarrierRequest;
use App\Http\Resources\CarrierResource;
use App\Models\Carrier;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarrierController extends Controller
{
    public function __construct(protected AuditLogService $auditLogService)
    {
    }

    public function index(Request $request)
    {
        $query = Carrier::query();

        if ($request->filled('transport_mode')) {
            $query->where('transport_mode', $request->string('transport_mode'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);

        return CarrierResource::collection($query->orderBy('name')->paginate($perPage));
    }

    public function store(StoreCarrierRequest $request): JsonResponse
    {
        $carrier = Carrier::create($request->validated());

        $this->auditLogService->log(
            $request->user(),
            'CARRIER_CREATED',
            Carrier::class,
            $carrier->id,
            null,
            $carrier->toArray()
        );

        return (new CarrierResource($carrier))
            ->additional([
                'success' => true,
                'message' => 'Carrier created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }
}

