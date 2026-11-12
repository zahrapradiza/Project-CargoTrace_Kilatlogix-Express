<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\User;
use App\Services\EtaService;
use App\Services\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CargoTraceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;
    private User $customer1;
    private User $customer2;
    private Carrier $carrier;
    private Location $origin;
    private Location $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'email' => 'admin@kilatlogix.test']);
        $this->operator = User::factory()->create(['role' => User::ROLE_OPERATOR, 'email' => 'staff@kilatlogix.test']);
        $this->customer1 = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'email' => 'customer1@kilatlogix.test']);
        $this->customer2 = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'email' => 'customer2@kilatlogix.test']);

        $this->carrier = Carrier::create([
            'code' => 'KLX-REG',
            'name' => 'KilatLogix Regular',
            'transport_mode' => 'ROAD',
            'is_active' => true,
        ]);

        $this->origin = Location::create([
            'code' => 'ID-BTJ-HUB',
            'name' => 'KilatLogix Banda Aceh Drop Point',
            'city' => 'Banda Aceh',
            'country' => 'Indonesia',
            'type' => 'HUB',
            'latitude' => 5.5483,
            'longitude' => 95.3238,
        ]);

        $this->destination = Location::create([
            'code' => 'NL-RTM-PORT',
            'name' => 'Port of Rotterdam',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'type' => 'PORT',
            'latitude' => 51.9496,
            'longitude' => 4.1453,
        ]);
    }

    public function test_valid_login_invalid_login_and_unauthenticated_access_blocked(): void
    {
        $this->getJson('/api/shipments')->assertStatus(401);
        $this->get('/admin/dashboard')->assertRedirect('/login');

        $this->postJson('/api/login', [
            'email' => 'staff@kilatlogix.test',
            'password' => 'wrong-password',
        ])->assertStatus(401);

        $this->postJson('/api/login', [
            'email' => 'staff@kilatlogix.test',
            'password' => 'password',
        ])->assertStatus(200)->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_staff_and_admin_can_create_shipment_while_customer_gets_403_for_create_or_other_customer_shipment(): void
    {
        Sanctum::actingAs($this->operator);

        $created = $this->postJson('/api/shipments', [
            'customer_id' => $this->customer2->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Gayo Coffee Exports',
            'weight_kg' => 500,
            'expected_departure' => now()->toIso8601String(),
            'expected_arrival' => now()->addDays(5)->toIso8601String(),
        ])->assertStatus(201);

        $shipmentId = $created->json('data.id');

        Sanctum::actingAs($this->customer1);
        $this->getJson("/api/shipments/{$shipmentId}")->assertStatus(403);
        $this->postJson('/api/shipments', [
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Blocked Shipment',
            'weight_kg' => 25,
            'expected_departure' => now()->toIso8601String(),
            'expected_arrival' => now()->addDays(2)->toIso8601String(),
        ])->assertStatus(403);
        $this->patchJson("/api/shipments/{$shipmentId}", ['cargo_type' => 'Tamper'])->assertStatus(403);
    }

    public function test_tracking_event_state_machine_rejects_illegal_and_terminal_transitions_with_422(): void
    {
        $shipment = app(ShipmentService::class)->create([
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Electronics',
            'weight_kg' => 120,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDays(3),
        ], $this->admin);

        Sanctum::actingAs($this->operator);

        foreach (['BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'] as $idx => $status) {
            $this->postJson("/api/shipments/{$shipment->id}/tracking", [
                'status' => $status,
                'event_time' => now()->addMinutes(($idx + 1) * 10)->toIso8601String(),
                'description' => "Transitioned to {$status}",
            ])->assertStatus(201);
        }

        // Illegal transition back from DELIVERED to IN_TRANSIT or BOOKED must return 422
        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'IN_TRANSIT',
            'event_time' => now()->addHour()->toIso8601String(),
            'description' => 'Illegal reopen',
        ])->assertStatus(422);

        $this->postJson("/api/shipments/{$shipment->id}/tracking", [
            'status' => 'BOOKED',
            'event_time' => now()->addHours(2)->toIso8601String(),
            'description' => 'Illegal backward transition',
        ])->assertStatus(422);
    }

    public function test_eta_service_deterministic_delay_and_severity_classification(): void
    {
        $expected = now()->addDays(4);
        $shipment = app(ShipmentService::class)->create([
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Industrial Equipment',
            'weight_kg' => 800,
            'expected_departure' => now(),
            'expected_arrival' => $expected,
        ], $this->operator);

        $etaService = app(EtaService::class);

        $etaService->evaluate($shipment, $expected->copy()->addHours(3), null, $this->operator);
        $this->assertTrue($shipment->fresh()->is_delayed);
        $this->assertEquals('LOW', $shipment->fresh()->delay_severity);

        $etaService->evaluate($shipment, $expected->copy()->addHours(15), null, $this->operator);
        $this->assertEquals('MEDIUM', $shipment->fresh()->delay_severity);

        $etaService->evaluate($shipment, $expected->copy()->addHours(36), null, $this->operator);
        $this->assertEquals('HIGH', $shipment->fresh()->delay_severity);
    }
}

