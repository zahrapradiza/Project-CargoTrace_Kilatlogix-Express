<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShipmentAuthorizationAndCrudTest extends TestCase
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

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $this->customer1 = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $this->customer2 = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->carrier = Carrier::create([
            'code' => 'KLX-AIR',
            'name' => 'KilatLogix AirFreight',
            'transport_mode' => 'AIR',
            'is_active' => true,
        ]);

        $this->origin = Location::create([
            'code' => 'ID-JKT',
            'name' => 'Jakarta Hub',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'type' => 'HUB',
            'latitude' => -6.1754,
            'longitude' => 106.8272,
        ]);

        $this->destination = Location::create([
            'code' => 'SG-SIN',
            'name' => 'Singapore Port',
            'city' => 'Singapore',
            'country' => 'Singapore',
            'type' => 'PORT',
            'latitude' => 1.2644,
            'longitude' => 103.8220,
        ]);
    }

    public function test_operator_can_create_update_and_delete_shipment_with_validation(): void
    {
        Sanctum::actingAs($this->operator);

        // Invalid: same origin & destination
        $this->postJson('/api/shipments', [
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->origin->id,
            'cargo_type' => 'Electronics',
            'weight_kg' => 120,
            'expected_departure' => now()->toIso8601String(),
            'expected_arrival' => now()->addDays(2)->toIso8601String(),
        ])->assertStatus(422);

        // Valid creation
        $response = $this->postJson('/api/shipments', [
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Semiconductors',
            'weight_kg' => 250.5,
            'volume_cbm' => 1.2,
            'expected_departure' => now()->toIso8601String(),
            'expected_arrival' => now()->addDays(3)->toIso8601String(),
            'items' => [
                ['item_name' => 'Wafer Box', 'sku' => 'WF-01', 'quantity' => 5, 'weight_kg' => 50.1, 'declared_value' => 5000],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.current_status', 'CREATED');

        $shipmentId = $response->json('data.id');
        $this->assertMatchesRegularExpression('/^KLX-\d{4}-\d{4}$/', $response->json('data.shipment_number'));
        $this->assertDatabaseHas('tracking_events', ['shipment_id' => $shipmentId, 'status' => 'CREATED']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'SHIPMENT_CREATED', 'entity_id' => $shipmentId]);

        $this->patchJson("/api/shipments/{$shipmentId}", [
            'cargo_type' => 'Updated Precision Optics',
        ])->assertStatus(200)
            ->assertJsonPath('data.cargo_type', 'Updated Precision Optics');

        $this->deleteJson("/api/shipments/{$shipmentId}")->assertStatus(200);
        $this->assertSoftDeleted('shipments', ['id' => $shipmentId]);
    }

    public function test_customer_cannot_view_other_customers_shipment_or_create_shipments(): void
    {
        Sanctum::actingAs($this->operator);

        $c2ShipmentResp = $this->postJson('/api/shipments', [
            'customer_id' => $this->customer2->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Private Pharma',
            'weight_kg' => 80,
            'expected_departure' => now()->toIso8601String(),
            'expected_arrival' => now()->addDays(2)->toIso8601String(),
        ])->assertStatus(201);

        $c2ShipmentId = $c2ShipmentResp->json('data.id');

        $c1ShipmentResp = $this->postJson('/api/shipments', [
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Retail Goods',
            'weight_kg' => 50,
            'expected_departure' => now()->toIso8601String(),
            'expected_arrival' => now()->addDays(2)->toIso8601String(),
        ])->assertStatus(201);

        $c1ShipmentId = $c1ShipmentResp->json('data.id');

        // Customer 1 on API
        Sanctum::actingAs($this->customer1);
        $this->getJson("/api/shipments/{$c2ShipmentId}")->assertStatus(403);
        $this->getJson("/api/shipments/{$c1ShipmentId}")->assertStatus(200);
        $this->postJson('/api/shipments', [
            'customer_id' => $this->customer1->id,
            'carrier_id' => $this->carrier->id,
            'origin_location_id' => $this->origin->id,
            'destination_location_id' => $this->destination->id,
            'cargo_type' => 'Unauthorized',
            'weight_kg' => 10,
            'expected_departure' => now()->toIso8601String(),
            'expected_arrival' => now()->addDays(1)->toIso8601String(),
        ])->assertStatus(403);

        // Customer 1 index list only contains own shipment
        $list = $this->getJson('/api/shipments')->assertStatus(200)->json('data');
        $this->assertCount(1, $list);
        $this->assertEquals($c1ShipmentId, $list[0]['id']);

        // Customer 1 on Web
        $this->actingAs($this->customer1)->get("/shipments/{$c2ShipmentId}")->assertStatus(403);
        $this->actingAs($this->customer1)->get("/shipments/{$c1ShipmentId}")->assertStatus(200);
    }
}
