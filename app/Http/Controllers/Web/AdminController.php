<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ExternalIntegrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected ExternalIntegrationService $externalIntegrationService
    ) {
    }

    public function dashboard(): View
    {
        $totalShipments = Shipment::count();
        $inTransitCount = Shipment::where('current_status', Shipment::STATUS_IN_TRANSIT)->count();
        $deliveredCount = Shipment::where('current_status', Shipment::STATUS_DELIVERED)->count();
        $delayedCount = Shipment::where('is_delayed', true)->count();
        $openExceptionsCount = ShipmentException::where('status', '!=', 'RESOLVED')->count();
        $onTimeRate = $totalShipments > 0
            ? round((($totalShipments - $delayedCount) / $totalShipments) * 100, 1)
            : 100.0;

        $statusBreakdown = Shipment::select('current_status', DB::raw('count(*) as total'))
            ->groupBy('current_status')
            ->pluck('total', 'current_status')
            ->toArray();

        $exceptionBreakdown = ShipmentException::select('exception_type', DB::raw('count(*) as total'))
            ->groupBy('exception_type')
            ->pluck('total', 'exception_type')
            ->toArray();

        $carrierPerformance = Carrier::withCount([
            'shipments',
            'shipments as delayed_shipments_count' => fn ($q) => $q->where('is_delayed', true),
        ])->get();

        $delayedShipments = Shipment::with(['customer', 'carrier', 'originLocation', 'destinationLocation'])
            ->where('is_delayed', true)
            ->orderByDesc('delay_hours')
            ->limit(6)
            ->get();

        $recentAuditLogs = AuditLog::with('user')->orderByDesc('id')->limit(8)->get();

        return view('admin.dashboard', compact(
            'totalShipments',
            'inTransitCount',
            'deliveredCount',
            'delayedCount',
            'openExceptionsCount',
            'onTimeRate',
            'statusBreakdown',
            'exceptionBreakdown',
            'carrierPerformance',
            'delayedShipments',
            'recentAuditLogs'
        ));
    }

    public function users(Request $request): View
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('company_name', 'like', $term);
            });
        }

        $users = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_CUSTOMER])],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::create($data);

        $this->auditLogService->log(
            $request->user(),
            'USER_CREATED',
            User::class,
            $user->id,
            null,
            ['name' => $user->name, 'email' => $user->email, 'role' => $user->role]
        );

        return back()->with('success', "User {$user->name} ({$user->role}) created.");
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_CUSTOMER])],
        ]);

        if ((int) $user->id === (int) $request->user()->id && $data['role'] !== User::ROLE_ADMIN) {
            return back()->with('error', 'You cannot demote your own active admin account.');
        }

        $oldRole = $user->role;
        $user->update(['role' => $data['role']]);

        $this->auditLogService->log(
            $request->user(),
            'USER_ROLE_UPDATED',
            User::class,
            $user->id,
            ['role' => $oldRole],
            ['role' => $user->role]
        );

        return back()->with('success', "Updated role for {$user->name} to {$user->role}.");
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ((int) $user->id === (int) $request->user()->id) {
            return back()->with('error', 'You cannot delete your own active admin account.');
        }

        if ($user->shipments()->exists()) {
            return back()->with('error', 'Cannot delete a customer with existing shipments.');
        }

        $old = ['name' => $user->name, 'email' => $user->email, 'role' => $user->role];
        $userId = $user->id;
        $user->delete();

        $this->auditLogService->log($request->user(), 'USER_DELETED', User::class, $userId, $old, null);

        return back()->with('success', 'User removed.');
    }

    public function carriers(): View
    {
        $carriers = Carrier::withCount('shipments')->orderBy('name')->paginate(15);

        return view('admin.carriers', compact('carriers'));
    }

    public function storeCarrier(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:carriers,code'],
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'transport_mode' => ['required', Rule::in(['AIR', 'SEA', 'ROAD', 'RAIL'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $carrier = Carrier::create($data);

        $this->auditLogService->log($request->user(), 'CARRIER_CREATED', Carrier::class, $carrier->id, null, $carrier->toArray());

        return back()->with('success', "Carrier {$carrier->name} added.");
    }

    public function updateCarrier(Request $request, Carrier $carrier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'transport_mode' => ['required', Rule::in(['AIR', 'SEA', 'ROAD', 'RAIL'])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $old = $carrier->toArray();
        $data['is_active'] = $request->boolean('is_active');
        $carrier->update($data);

        $this->auditLogService->log($request->user(), 'CARRIER_UPDATED', Carrier::class, $carrier->id, $old, $carrier->fresh()->toArray());

        return back()->with('success', "Carrier {$carrier->name} updated.");
    }

    public function destroyCarrier(Request $request, Carrier $carrier): RedirectResponse
    {
        if ($carrier->shipments()->exists()) {
            return back()->with('error', 'Cannot delete carrier assigned to existing shipments.');
        }

        $old = $carrier->toArray();
        $id = $carrier->id;
        $carrier->delete();

        $this->auditLogService->log($request->user(), 'CARRIER_DELETED', Carrier::class, $id, $old, null);

        return back()->with('success', 'Carrier deleted.');
    }

    public function locations(): View
    {
        $locations = Location::orderBy('country')->orderBy('name')->paginate(15);

        return view('admin.locations', compact('locations'));
    }

    public function storeLocation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:locations,code'],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['HUB', 'PORT', 'AIRPORT', 'WAREHOUSE', 'CUSTOMER_ADDR'])],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $location = Location::create($data);

        $this->auditLogService->log($request->user(), 'LOCATION_CREATED', Location::class, $location->id, null, $location->toArray());

        return back()->with('success', "Location {$location->name} added.");
    }

    public function destroyLocation(Request $request, Location $location): RedirectResponse
    {
        if ($location->originShipments()->exists() || $location->destinationShipments()->exists()) {
            return back()->with('error', 'Cannot delete location referenced by existing shipments.');
        }

        $old = $location->toArray();
        $id = $location->id;
        $location->delete();

        $this->auditLogService->log($request->user(), 'LOCATION_DELETED', Location::class, $id, $old, null);

        return back()->with('success', 'Location deleted.');
    }

    public function geocode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:200'],
        ]);

        return response()->json($this->externalIntegrationService->geocodeAddress($data['q']));
    }

    public function auditLogs(Request $request): View
    {
        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('action', 'like', $term)
                    ->orWhere('entity_type', 'like', $term);
            });
        }

        $logs = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit-logs', compact('logs', 'actions'));
    }

    public function settings(): View
    {
        $config = config('cargotrace');

        return view('admin.settings', compact('config'));
    }
}
