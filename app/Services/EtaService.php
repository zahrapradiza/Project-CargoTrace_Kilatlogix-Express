<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;

class EtaService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected AuditLogService $auditLogService
    ) {
    }

    /**
     * Evaluate ETA against expected arrival, compute deterministic delay severity,
     * persist updates, and notify customer if delayed.
     */
    public function evaluate(
        Shipment $shipment,
        Carbon|string|null $estimatedArrival = null,
        ?string $customReason = null,
        ?User $actor = null
    ): Shipment {
        $eta = match (true) {
            $estimatedArrival instanceof Carbon => $estimatedArrival,
            is_string($estimatedArrival) && $estimatedArrival !== '' => Carbon::parse($estimatedArrival),
            default => $shipment->estimated_arrival,
        };

        $expected = $shipment->expected_arrival;
        $wasDelayed = (bool) $shipment->is_delayed;
        $oldDelayHours = (int) $shipment->delay_hours;

        if (! $eta || ! $expected || $eta->lessThanOrEqualTo($expected)) {
            $shipment->estimated_arrival = $eta;
            $shipment->is_delayed = false;
            $shipment->delay_hours = 0;
            $shipment->delay_severity = 'NONE';
            $shipment->delay_reason = null;
            $shipment->save();

            return $shipment;
        }

        $delayMinutes = $expected->diffInMinutes($eta);
        $delayHours = max(1, (int) ceil($delayMinutes / 60));
        $thresholdHours = (int) config('cargotrace.delay_threshold_hours', 12);
        $severity = $this->classifySeverity($delayHours);

        $reason = $customReason ?: ($delayHours >= $thresholdHours
            ? "Estimated arrival exceeds expected schedule by {$delayHours} hours (above {$thresholdHours}h threshold)."
            : "Estimated arrival delayed by {$delayHours} hours.");

        $oldValues = [
            'is_delayed' => $wasDelayed,
            'delay_hours' => $oldDelayHours,
            'delay_severity' => $shipment->delay_severity,
            'estimated_arrival' => $shipment->estimated_arrival?->toIso8601String(),
        ];

        $shipment->estimated_arrival = $eta;
        $shipment->is_delayed = true;
        $shipment->delay_hours = $delayHours;
        $shipment->delay_severity = $severity;
        $shipment->delay_reason = $reason;
        $shipment->save();

        if (! $wasDelayed || $delayHours !== $oldDelayHours) {
            $this->auditLogService->log(
                $actor,
                'DELAY_EVALUATED',
                Shipment::class,
                $shipment->id,
                $oldValues,
                [
                    'is_delayed' => true,
                    'delay_hours' => $delayHours,
                    'delay_severity' => $severity,
                    'estimated_arrival' => $eta->toIso8601String(),
                ]
            );

            $this->notificationService->notifyShipmentCustomer(
                $shipment,
                "Shipment {$shipment->shipment_number} Delay Alert ({$severity})",
                "Your shipment {$shipment->shipment_number} is delayed by {$delayHours} hour(s). {$reason}",
                'DELAY'
            );
        }

        return $shipment;
    }

    public function classifySeverity(int $delayHours): string
    {
        if ($delayHours <= 0) {
            return 'NONE';
        }

        $lowMax = (int) config('cargotrace.delay_severity_thresholds.low_max_hours', 6);
        $mediumMax = (int) config('cargotrace.delay_severity_thresholds.medium_max_hours', 24);

        return match (true) {
            $delayHours <= $lowMax => 'LOW',
            $delayHours <= $mediumMax => 'MEDIUM',
            default => 'HIGH',
        };
    }
}
