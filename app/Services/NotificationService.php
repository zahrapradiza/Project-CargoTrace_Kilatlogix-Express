<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Shipment;
use App\Models\User;

class NotificationService
{
    public function notifyUser(
        User|int $user,
        string $title,
        string $message,
        string $type = 'INFO',
        ?Shipment $shipment = null
    ): Notification {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'shipment_id' => $shipment?->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
        ]);
    }

    public function notifyShipmentCustomer(
        Shipment $shipment,
        string $title,
        string $message,
        string $type = 'INFO'
    ): ?Notification {
        if (! $shipment->customer_id) {
            return null;
        }

        return $this->notifyUser(
            $shipment->customer_id,
            $title,
            $message,
            $type,
            $shipment
        );
    }

    public function markAsRead(Notification $notification): Notification
    {
        $notification->update(['is_read' => true]);

        return $notification;
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
