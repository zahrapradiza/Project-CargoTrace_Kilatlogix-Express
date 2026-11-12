<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExceptionService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected NotificationService $notificationService
    ) {
    }

    public function openException(Shipment $shipment, array $data, User $actor): ShipmentException
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $detectedAt = isset($data['detected_at'])
                ? Carbon::parse($data['detected_at'])
                : now();

            $exception = ShipmentException::create([
                'shipment_id' => $shipment->id,
                'reported_by' => $actor->id,
                'exception_type' => $data['exception_type'],
                'severity' => $data['severity'],
                'status' => 'OPEN',
                'description' => $data['description'],
                'detected_at' => $detectedAt,
            ]);

            $latestEvent = $shipment->trackingEvents()->reorder('event_time', 'desc')->orderByDesc('id')->first();
            $eventTime = $latestEvent && $detectedAt->lt($latestEvent->event_time)
                ? $latestEvent->event_time->copy()->addSecond()
                : $detectedAt;

            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'location_id' => $data['location_id'] ?? $latestEvent?->location_id ?? $shipment->origin_location_id,
                'created_by' => $actor->id,
                'status' => 'EXCEPTION',
                'event_time' => $eventTime,
                'latitude' => $data['latitude'] ?? $latestEvent?->latitude ?? $shipment->originLocation?->latitude,
                'longitude' => $data['longitude'] ?? $latestEvent?->longitude ?? $shipment->originLocation?->longitude,
                'description' => "[{$exception->exception_type} - {$exception->severity}] {$exception->description}",
                'is_exception' => true,
            ]);

            $this->auditLogService->log(
                $actor,
                'EXCEPTION_OPENED',
                ShipmentException::class,
                $exception->id,
                null,
                $exception->toArray()
            );

            $this->notificationService->notifyShipmentCustomer(
                $shipment,
                "Shipment Exception Alert: {$shipment->shipment_number}",
                "{$exception->exception_type} ({$exception->severity}): {$exception->description}",
                'EXCEPTION'
            );

            return $exception->fresh(['reporter', 'resolver', 'shipment']);
        });
    }

    public function updateException(ShipmentException $exception, array $data, User $actor): ShipmentException
    {
        return DB::transaction(function () use ($exception, $data, $actor) {
            $oldValues = $exception->toArray();

            $updateData = collect($data)->only([
                'status',
                'severity',
                'resolution_notes',
            ])->toArray();

            if (($updateData['status'] ?? null) === 'RESOLVED') {
                $updateData['resolved_by'] = $actor->id;
                $updateData['resolved_at'] = isset($data['resolved_at'])
                    ? Carbon::parse($data['resolved_at'])
                    : now();
            }

            $exception->fill($updateData);
            $exception->save();

            $action = ($exception->status === 'RESOLVED' && ($oldValues['status'] ?? null) !== 'RESOLVED')
                ? 'EXCEPTION_RESOLVED'
                : 'EXCEPTION_UPDATED';

            $this->auditLogService->log(
                $actor,
                $action,
                ShipmentException::class,
                $exception->id,
                $oldValues,
                $exception->fresh()->toArray()
            );

            if ($exception->shipment) {
                $this->notificationService->notifyShipmentCustomer(
                    $exception->shipment,
                    "Exception {$exception->status}: {$exception->shipment->shipment_number}",
                    $exception->resolution_notes ?: "Exception status updated to {$exception->status}.",
                    $exception->status === 'RESOLVED' ? 'INFO' : 'EXCEPTION'
                );
            }

            return $exception->fresh(['reporter', 'resolver', 'shipment']);
        });
    }
}
