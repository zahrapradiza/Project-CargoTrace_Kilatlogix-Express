<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\User;
use App\Services\EtaService;
use App\Services\ExceptionService;
use App\Services\ExternalIntegrationService;
use App\Services\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EtaAndExceptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deterministic_eta_delay_calculation_and_severity_classification(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $carrier = Carrier::create(['code' => 'MAERSK', 'name' => 'Maersk', 'transport_mode' => 'SEA', 'is_active' => true]);
        $origin = Location::create(['code' => 'ORG', 'name' => 'Origin', 'city' => 'Jakarta', 'country' => 'ID', 'type' => 'PORT', 'latitude' => -6.2, 'longitude' => 106.8]);
        $dest = Location::create(['code' => 'DST', 'name' => 'Dest', 'city' => 'Rotterdam', 'country' => 'NL', 'type' => 'PORT', 'latitude' => 51.9, 'longitude' => 4.1]);

        $expectedArrival = now()->addDays(5);

        $shipment = app(ShipmentService::class)->create([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_location_id' => $origin->id,
            'destination_location_id' => $dest->id,
            'cargo_type' => 'Machinery',
            'weight_kg' => 1200,
            'expected_departure' => now(),
            'expected_arrival' => $expectedArrival,
        ], $operator);

        $etaService = app(EtaService::class);

        // On time => NONE
        $etaService->evaluate($shipment, $expectedArrival->copy(), null, $operator);
        $this->assertFalse($shipment->fresh()->is_delayed);
        $this->assertEquals('NONE', $shipment->fresh()->delay_severity);

        // +4 hours => LOW
        $etaService->evaluate($shipment, $expectedArrival->copy()->addHours(4), null, $operator);
        $this->assertTrue($shipment->fresh()->is_delayed);
        $this->assertEquals(4, $shipment->fresh()->delay_hours);
        $this->assertEquals('LOW', $shipment->fresh()->delay_severity);

        // +14 hours => MEDIUM
        $etaService->evaluate($shipment, $expectedArrival->copy()->addHours(14), null, $operator);
        $this->assertEquals(14, $shipment->fresh()->delay_hours);
        $this->assertEquals('MEDIUM', $shipment->fresh()->delay_severity);

        // +30 hours => HIGH
        $etaService->evaluate($shipment, $expectedArrival->copy()->addHours(30), null, $operator);
        $this->assertEquals(30, $shipment->fresh()->delay_hours);
        $this->assertEquals('HIGH', $shipment->fresh()->delay_severity);

        // Customer received delay notifications
        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'shipment_id' => $shipment->id,
            'type' => 'DELAY',
        ]);
    }

    public function test_exception_lifecycle_and_external_api_fallback(): void
    {
        $operator = User::factory()->create(['role' => User::ROLE_OPERATOR]);
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $carrier = Carrier::create(['code' => 'KLX-CARGO', 'name' => 'KLX Cargo', 'transport_mode' => 'ROAD', 'is_active' => true]);
        $origin = Location::create(['code' => 'ORG', 'name' => 'Origin', 'city' => 'Jakarta', 'country' => 'ID', 'type' => 'HUB', 'latitude' => -6.2, 'longitude' => 106.8]);
        $dest = Location::create(['code' => 'DST', 'name' => 'Dest', 'city' => 'Bandung', 'country' => 'ID', 'type' => 'WAREHOUSE', 'latitude' => -6.9, 'longitude' => 107.6]);

        $shipment = app(ShipmentService::class)->create([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_location_id' => $origin->id,
            'destination_location_id' => $dest->id,
            'cargo_type' => 'Spare Parts',
            'weight_kg' => 300,
            'expected_departure' => now(),
            'expected_arrival' => now()->addDay(),
        ], $operator);

        $exceptionService = app(ExceptionService::class);
        $exception = $exceptionService->openException($shipment, [
            'exception_type' => 'CUSTOMS_HOLD',
            'severity' => 'HIGH',
            'description' => 'Pending customs clearance document.',
        ], $operator);

        $this->assertEquals('OPEN', $exception->status);
        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $shipment->id,
            'status' => 'EXCEPTION',
            'is_exception' => true,
        ]);

        $resolved = $exceptionService->updateException($exception, [
            'status' => 'RESOLVED',
            'resolution_notes' => 'Clearance approved.',
        ], $operator);

        $this->assertEquals('RESOLVED', $resolved->status);
        $this->assertNotNull($resolved->resolved_at);
        $this->assertEquals($operator->id, $resolved->resolved_by);

        // ExternalIntegrationService handles provider failure gracefully
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        $weather = app(ExternalIntegrationService::class)->getWeather(-6.2, 106.8);
        $this->assertFalse($weather['available']);
        $this->assertEquals('Fallback (Offline/Timeout)', $weather['source']);
    }
}

