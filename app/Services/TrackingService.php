<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrackingService
{
    public function __construct(
        protected ShipmentService $shipmentService,
        protected EtaService $etaService,
        protected AuditLogService $auditLogService,
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Append a new historical tracking event (never overwrites old events),
     * validate chronological order and status state machine, and sync parent shipment.
     *
     * @throws ValidationException
     */
    public function addEvent(Shipment $shipment, array $data, User $actor): TrackingEvent
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $eventTime = isset($data['event_time'])
                ? Carbon::parse($data['event_time'])
                : now();

            if ($shipment->created_at && $eventTime->lt($shipment->created_at->copy()->subSeconds(5))) {
                throw ValidationException::withMessages([
                    'event_time' => 'Tracking event time cannot be earlier than shipment creation time.',
                ]);
            }

            $latestEvent = $shipment->trackingEvents()->reorder('event_time', 'desc')->orderByDesc('id')->first();
            if ($latestEvent && $eventTime->lt($latestEvent->event_time)) {
                throw ValidationException::withMessages([
                    'event_time' => 'Tracking event time must be chronological and cannot precede the latest event.',
                ]);
            }

            $newStatus = $data['status'];
            $isException = $newStatus === 'EXCEPTION' || (bool) ($data['is_exception'] ?? false);

            if ($newStatus === 'EXCEPTION') {
                if (in_array($shipment->current_status, [Shipment::STATUS_DELIVERED, Shipment::STATUS_CANCELLED], true)) {
                    throw ValidationException::withMessages([
                        'status' => "Cannot record an exception event on a terminal {$shipment->current_status} shipment.",
                    ]);
                }
            } else {
                $this->shipmentService->validateStatusTransition(
                    $shipment->current_status,
                    $newStatus,
                    allowSameInProgress: true
                );
            }

            $location = isset($data['location_id']) ? Location::find($data['location_id']) : null;
            $latitude = $data['latitude'] ?? $location?->latitude;
            $longitude = $data['longitude'] ?? $location?->longitude;

            $event = TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'location_id' => $location?->id,
                'created_by' => $actor->id,
                'status' => $newStatus,
                'event_time' => $eventTime,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'description' => $data['description'],
                'is_exception' => $isException,
            ]);

            $oldStatus = $shipment->current_status;
            if ($newStatus !== 'EXCEPTION') {
                $shipment->current_status = $newStatus;
                if ($newStatus === Shipment::STATUS_DELIVERED && ! $shipment->actual_arrival) {
                    $shipment->actual_arrival = $eventTime;
                }
                $shipment->save();
            }

            if (! empty($data['estimated_arrival'])) {
                $this->etaService->evaluate(
                    $shipment,
                    $data['estimated_arrival'],
                    $data['delay_reason'] ?? null,
                    $actor
                );
            } elseif ($shipment->estimated_arrival) {
                $this->etaService->evaluate($shipment, $shipment->estimated_arrival, null, $actor);
            }

            $this->auditLogService->log(
                $actor,
                $oldStatus !== $shipment->current_status ? 'STATUS_CHANGED' : 'TRACKING_EVENT_ADDED',
                TrackingEvent::class,
                $event->id,
                ['current_status' => $oldStatus],
                [
                    'event_status' => $newStatus,
                    'current_status' => $shipment->current_status,
                    'location_id' => $location?->id,
                    'event_time' => $eventTime->toIso8601String(),
                ]
            );

            $notifType = match ($newStatus) {
                Shipment::STATUS_DELIVERED => 'DELIVERED',
                'EXCEPTION' => 'EXCEPTION',
                default => 'INFO',
            };

            $this->notificationService->notifyShipmentCustomer(
                $shipment,
                "Tracking Update ({$newStatus}): {$shipment->shipment_number}",
                $data['description'],
                $notifType
            );

            return $event->fresh(['location', 'creator']);
        });
    }
}

