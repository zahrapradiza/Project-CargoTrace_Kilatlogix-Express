<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShipmentService
{
    public function __construct(
        protected EtaService $etaService,
        protected AuditLogService $auditLogService,
        protected NotificationService $notificationService
    ) {
    }

    public function create(array $data, User $actor): Shipment
    {
        return DB::transaction(function () use ($data, $actor) {
            $origin = Location::findOrFail($data['origin_location_id']);
            $shipmentNumber = $data['shipment_number'] ?? $this->generateShipmentNumber();

            $shipment = Shipment::create([
                'shipment_number' => $shipmentNumber,
                'customer_id' => $data['customer_id'],
                'carrier_id' => $data['carrier_id'],
                'origin_location_id' => $data['origin_location_id'],
                'destination_location_id' => $data['destination_location_id'],
                'cargo_type' => $data['cargo_type'],
                'weight_kg' => $data['weight_kg'],
                'volume_cbm' => $data['volume_cbm'] ?? 0,
                'expected_departure' => $data['expected_departure'],
                'expected_arrival' => $data['expected_arrival'],
                'estimated_arrival' => $data['estimated_arrival'] ?? $data['expected_arrival'],
                'current_status' => Shipment::STATUS_CREATED,
                'is_delayed' => false,
                'delay_hours' => 0,
                'delay_severity' => 'NONE',
                'delay_reason' => $data['delay_reason'] ?? null,
            ]);

            if (! empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $shipment->items()->create([
                        'item_name' => $item['item_name'],
                        'sku' => $item['sku'],
                        'quantity' => $item['quantity'] ?? 1,
                        'weight_kg' => $item['weight_kg'],
                        'declared_value' => $item['declared_value'] ?? 0,
                    ]);
                }
            }

            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'location_id' => $origin->id,
                'created_by' => $actor->id,
                'status' => Shipment::STATUS_CREATED,
                'event_time' => now(),
                'latitude' => $origin->latitude,
                'longitude' => $origin->longitude,
                'description' => "Shipment {$shipment->shipment_number} registered at {$origin->name} ({$origin->city}).",
                'is_exception' => false,
            ]);

            $this->etaService->evaluate(
                $shipment,
                $shipment->estimated_arrival,
                $data['delay_reason'] ?? null,
                $actor
            );

            $this->auditLogService->log(
                $actor,
                'SHIPMENT_CREATED',
                Shipment::class,
                $shipment->id,
                null,
                $shipment->fresh()->toArray()
            );

            $this->notificationService->notifyShipmentCustomer(
                $shipment,
                "Shipment Created: {$shipment->shipment_number}",
                "Your shipment {$shipment->shipment_number} ({$shipment->cargo_type}) has been registered.",
                'INFO'
            );

            return $shipment->fresh([
                'customer',
                'carrier',
                'originLocation',
                'destinationLocation',
                'items',
                'trackingEvents.location',
                'exceptions',
            ]);
        });
    }

    public function update(Shipment $shipment, array $data, User $actor): Shipment
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $oldValues = $shipment->toArray();

            if (isset($data['current_status']) && $data['current_status'] !== $shipment->current_status) {
                $this->validateStatusTransition($shipment->current_status, $data['current_status']);
            }

            $fillableData = collect($data)->only([
                'customer_id',
                'carrier_id',
                'origin_location_id',
                'destination_location_id',
                'cargo_type',
                'weight_kg',
                'volume_cbm',
                'expected_departure',
                'expected_arrival',
                'estimated_arrival',
                'actual_arrival',
                'current_status',
                'delay_reason',
            ])->toArray();

            if (($fillableData['current_status'] ?? null) === Shipment::STATUS_DELIVERED && empty($fillableData['actual_arrival'])) {
                $fillableData['actual_arrival'] = now();
            }

            $shipment->fill($fillableData);
            $shipment->save();

            if (array_key_exists('estimated_arrival', $data) || array_key_exists('expected_arrival', $data)) {
                $this->etaService->evaluate(
                    $shipment,
                    $shipment->estimated_arrival,
                    $data['delay_reason'] ?? null,
                    $actor
                );
            }

            $action = ($oldValues['current_status'] !== $shipment->current_status)
                ? 'STATUS_CHANGED'
                : 'SHIPMENT_UPDATED';

            $this->auditLogService->log(
                $actor,
                $action,
                Shipment::class,
                $shipment->id,
                $oldValues,
                $shipment->fresh()->toArray()
            );

            return $shipment->fresh([
                'customer',
                'carrier',
                'originLocation',
                'destinationLocation',
                'items',
                'trackingEvents.location',
                'exceptions',
            ]);
        });
    }

    public function delete(Shipment $shipment, User $actor): void
    {
        DB::transaction(function () use ($shipment, $actor) {
            $oldValues = $shipment->toArray();
            $shipment->delete();

            $this->auditLogService->log(
                $actor,
                'SHIPMENT_DELETED',
                Shipment::class,
                $shipment->id,
                $oldValues,
                null
            );
        });
    }

    /**
     * Validate Shipment Lifecycle State Machine transitions.
     *
     * @throws ValidationException
     */
    public function validateStatusTransition(string $fromStatus, string $toStatus, bool $allowSameInProgress = false): void
    {
        if (in_array($fromStatus, [Shipment::STATUS_DELIVERED, Shipment::STATUS_CANCELLED], true)) {
            throw ValidationException::withMessages([
                'status' => "Terminal status {$fromStatus} cannot transition to {$toStatus}.",
            ]);
        }

        if ($allowSameInProgress && $fromStatus === $toStatus) {
            return;
        }

        $allowedTransitions = config("cargotrace.status_transitions.{$fromStatus}", []);

        if (! in_array($toStatus, $allowedTransitions, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status transition from {$fromStatus} to {$toStatus}.",
            ]);
        }
    }

    public function generateShipmentNumber(?Carbon $date = null): string
    {
        $prefix = config('cargotrace.shipment_prefix', 'KLX');
        $year = ($date ?? now())->format('Y');
        $pattern = "{$prefix}-{$year}-";

        $latestNumber = Shipment::withTrashed()
            ->where('shipment_number', 'like', "{$pattern}%")
            ->orderByDesc('id')
            ->value('shipment_number');

        $nextSequence = 1;
        if ($latestNumber && preg_match('/-(\d+)$/', $latestNumber, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $pattern, $nextSequence);
    }
}
