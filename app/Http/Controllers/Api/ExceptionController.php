<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExceptionRequest;
use App\Http\Requests\Api\UpdateExceptionRequest;
use App\Http\Resources\ShipmentExceptionResource;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Services\ExceptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExceptionController extends Controller
{
    public function __construct(protected ExceptionService $exceptionService)
    {
    }

    public function index(Request $request, Shipment $shipment)
    {
        Gate::authorize('view', $shipment);

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);

        return ShipmentExceptionResource::collection(
            $shipment->exceptions()->with(['reporter', 'resolver'])->paginate($perPage)
        );
    }

    public function store(StoreExceptionRequest $request, Shipment $shipment): JsonResponse
    {
        Gate::authorize('manageException', $shipment);

        $exception = $this->exceptionService->openException($shipment, $request->validated(), $request->user());

        return (new ShipmentExceptionResource($exception))
            ->additional([
                'success' => true,
                'message' => 'Shipment exception logged successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateExceptionRequest $request, ShipmentException $exception): ShipmentExceptionResource
    {
        Gate::authorize('manageException', $exception->shipment);

        $updated = $this->exceptionService->updateException($exception, $request->validated(), $request->user());

        return (new ShipmentExceptionResource($updated))->additional([
            'success' => true,
            'message' => 'Shipment exception updated successfully.',
        ]);
    }
}
