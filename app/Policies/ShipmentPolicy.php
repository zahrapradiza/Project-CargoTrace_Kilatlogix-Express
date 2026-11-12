<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR, User::ROLE_CUSTOMER]);
    }

    public function view(User $user, Shipment $shipment): bool
    {
        if ($user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR])) {
            return true;
        }

        return $user->isCustomer() && (int) $shipment->customer_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]);
    }

    public function update(User $user, Shipment $shipment): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]);
    }

    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]);
    }

    public function addTrackingEvent(User $user, Shipment $shipment): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]);
    }

    public function manageException(User $user, Shipment $shipment): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_OPERATOR]);
    }
}

