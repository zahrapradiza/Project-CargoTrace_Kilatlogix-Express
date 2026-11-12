<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(protected AuditLogService $auditLogService)
    {
    }

    public function index(Request $request)
    {
        $query = Location::query();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('country')) {
            $query->where('country', $request->string('country'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('city', 'like', $term);
            });
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);

        return LocationResource::collection($query->orderBy('name')->paginate($perPage));
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $location = Location::create($request->validated());

        $this->auditLogService->log(
            $request->user(),
            'LOCATION_CREATED',
            Location::class,
            $location->id,
            null,
            $location->toArray()
        );

        return (new LocationResource($location))
            ->additional([
                'success' => true,
                'message' => 'Location created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }
}
