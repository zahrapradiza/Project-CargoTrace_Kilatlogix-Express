<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\User;
use App\Services\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TrackingStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_forward_lifecycle_transitions_and_rejection_of_invalid_or_terminal_transitions(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $carrier = Carrier::create(['code' => 'KLX-EXP', 'name' => 'KLX Express', 'transport_mode' => 'AIR', 'is_active' => true]);
        $origin = Location::create(['code' => 'ORG', 'name' => 'Origin', 'city' => 'Jakarta', 'country' => 'ID', 'type' => 'HUB', 'latitude' => -6.2, 'longitude' => 106.8]);
        $dest = Location::create(['code' => 'DST', 'name' => 'Dest', 'city' => 'Sydney', 'country' => 'AU', 'type' => 'PORT', 'latitude' => -33.9, 'longitude' => 151.2]);

        $shipment = app(ShipmentService::class)->create([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_location_id' => $origin->id,
            'destination_location_id' => $dest->id,
            'cargo_type' => 'Medical Equipment',
            'weight_kg' => 100,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(4),
        ], $operator);

        Sanctum::actingAs($operator);

        // Invalid skip: CREATED -> IN_TRANSIT must fail with 422
        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'IN_TRANSIT',
            'description' => 'Skipped booking and pickup',
        ])->assertStatus(422);

        // Valid sequential progression: BOOKED -> PICKED_UP -> IN_TRANSIT
        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'BOOKED',
            'event_time' => now()->addMinutes(5)->toIso8601String(),
            'description' => 'Booked air space',
        ])->assertStatus(201);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'PICKED_UP',
            'event_time' => now()->addMinutes(10)->toIso8601String(),
            'description' => 'Picked up by courier',
        ])->assertStatus(201);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'IN_TRANSIT',
            'event_time' => now()->addMinutes(15)->toIso8601String(),
            'description' => 'Departed origin hub',
        ])->assertStatus(201);

        // Chronological order violation must fail with 422
        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'IN_TRANSIT',
            'event_time' => now()->subHours(2)->toIso8601String(),
            'description' => 'Backdated checkpoint',
        ])->assertStatus(422);

        // Backward status transition IN_TRANSIT -> BOOKED must fail with 422
        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'BOOKED',
            'event_time' => now()->addMinutes(20)->toIso8601String(),
            'description' => 'Invalid backward transition',
        ])->assertStatus(422);

        // Valid terminal transition: IN_TRANSIT -> DELIVERED
        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'DELIVERED',
            'event_time' => now()->addMinutes(30)->toIso8601String(),
            'description' => 'Signed by recipient',
        ])->assertStatus(201);

        // Terminal DELIVERED -> IN_TRANSIT must fail with 422
        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'IN_TRANSIT',
            'event_time' => now()->addMinutes(40)->toIso8601String(),
            'description' => 'Attempt to reopen delivered shipment',
        ])->assertStatus(422);

        // Verify append-only historical events (CREATED + BOOKED + PICKED_UP + IN_TRANSIT + DELIVERED = 5)
        $this->assertEquals(5, $shipment->trackingEvents()->count());
        $this->assertEquals('DELIVERED', $shipment->fresh()->current_status);
    }
}
