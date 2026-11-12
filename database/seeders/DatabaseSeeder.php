<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\EtaService;
use App\Services\ExceptionService;
use App\Services\NotificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        // 1. Demo Users (Admin, Staff/Operator, Customer 1, Customer 2)
        $admin = User::create([
            'name' => 'Aulia Pratama (Admin)',
            'email' => 'admin@kilatlogix.test',
            'password' => $password,
            'role' => User::ROLE_ADMIN,
            'phone' => '+62-21-5550-0001',
            'company_name' => 'KilatLogix Express HQ',
        ]);

        $operator = User::create([
            'name' => 'Budi Santoso (Hub Officer)',
            'email' => 'staff@kilatlogix.test',
            'password' => $password,
            'role' => User::ROLE_OPERATOR,
            'phone' => '+62-21-5550-0002',
            'company_name' => 'KilatLogix Jakarta Mega Hub',
        ]);

        $customer1 = User::create([
            'name' => 'Siti Rahmawati',
            'email' => 'customer1@kilatlogix.test',
            'password' => $password,
            'role' => User::ROLE_CUSTOMER,
            'phone' => '+62-812-8800-1101',
            'company_name' => 'Nusantara Tech Imports',
        ]);

        $customer2 = User::create([
            'name' => 'Marcus Tan',
            'email' => 'customer2@kilatlogix.test',
            'password' => $password,
            'role' => User::ROLE_CUSTOMER,
            'phone' => '+65-9123-4402',
            'company_name' => 'Pacific Maritime Trading',
        ]);

        // 2. Carriers
        $carriers = collect([
            ['code' => 'KLX-EXP', 'name' => 'KilatLogix Super Express', 'contact_email' => 'express@kilatlogix.test', 'phone' => '+62-21-5550-1000', 'transport_mode' => 'AIR', 'is_active' => true],
            ['code' => 'KLX-CARGO', 'name' => 'KilatLogix Heavy Cargo', 'contact_email' => 'cargo@kilatlogix.test', 'phone' => '+62-21-5550-2000', 'transport_mode' => 'ROAD', 'is_active' => true],
            ['code' => 'KLX-AIR', 'name' => 'KilatLogix AirFreight', 'contact_email' => 'airfreight@kilatlogix.test', 'phone' => '+65-6555-3000', 'transport_mode' => 'AIR', 'is_active' => true],
            ['code' => 'MAERSK', 'name' => 'Maersk SeaLine', 'contact_email' => 'ops@maersk-partner.test', 'phone' => '+31-10-555-4000', 'transport_mode' => 'SEA', 'is_active' => true],
        ])->mapWithKeys(fn ($c) => [$c['code'] => Carrier::create($c)]);

        // 3. 12 Global Locations across Southeast Asia, Europe, North America, and Australia
        $locationsData = [
            ['code' => 'ID-JKT-HUB', 'name' => 'KilatLogix Jakarta Mega Hub', 'city' => 'Jakarta', 'country' => 'Indonesia', 'type' => 'HUB', 'latitude' => -6.1754000, 'longitude' => 106.8272000],
            ['code' => 'ID-SUB-PORT', 'name' => 'Tanjung Perak Port Hub', 'city' => 'Surabaya', 'country' => 'Indonesia', 'type' => 'PORT', 'latitude' => -7.2043000, 'longitude' => 112.7332000],
            ['code' => 'ID-BDG-WH', 'name' => 'Bandung Logistics Warehouse', 'city' => 'Bandung', 'country' => 'Indonesia', 'type' => 'WAREHOUSE', 'latitude' => -6.9175000, 'longitude' => 107.6191000],
            ['code' => 'SG-SIN-PORT', 'name' => 'Port of Singapore Terminal', 'city' => 'Singapore', 'country' => 'Singapore', 'type' => 'PORT', 'latitude' => 1.2644000, 'longitude' => 103.8220000],
            ['code' => 'MY-PKG-PORT', 'name' => 'Port Klang Distribution Hub', 'city' => 'Klang', 'country' => 'Malaysia', 'type' => 'PORT', 'latitude' => 3.0005000, 'longitude' => 101.3919000],
            ['code' => 'TH-BKK-AIR', 'name' => 'Suvarnabhumi Cargo Terminal', 'city' => 'Bangkok', 'country' => 'Thailand', 'type' => 'AIRPORT', 'latitude' => 13.6900000, 'longitude' => 100.7501000],
            ['code' => 'NL-RTM-PORT', 'name' => 'Port of Rotterdam Maasvlakte', 'city' => 'Rotterdam', 'country' => 'Netherlands', 'type' => 'PORT', 'latitude' => 51.9496000, 'longitude' => 4.1453000],
            ['code' => 'DE-FRA-AIR', 'name' => 'Frankfurt CargoCity Hub', 'city' => 'Frankfurt', 'country' => 'Germany', 'type' => 'AIRPORT', 'latitude' => 50.0379000, 'longitude' => 8.5622000],
            ['code' => 'US-LAX-PORT', 'name' => 'Port of Los Angeles Gateway', 'city' => 'Los Angeles', 'country' => 'USA', 'type' => 'PORT', 'latitude' => 33.7361000, 'longitude' => -118.2615000],
            ['code' => 'US-JFK-AIR', 'name' => 'JFK International Cargo Plaza', 'city' => 'New York', 'country' => 'USA', 'type' => 'AIRPORT', 'latitude' => 40.6413000, 'longitude' => -73.7781000],
            ['code' => 'AU-SYD-PORT', 'name' => 'Port Botany Cargo Terminal', 'city' => 'Sydney', 'country' => 'Australia', 'type' => 'PORT', 'latitude' => -33.9675000, 'longitude' => 151.2212000],
            ['code' => 'AU-MEL-WH', 'name' => 'Melbourne Tullamarine Fulfillment', 'city' => 'Melbourne', 'country' => 'Australia', 'type' => 'WAREHOUSE', 'latitude' => -37.6690000, 'longitude' => 144.8410000],
        ];
        $locations = collect($locationsData)->mapWithKeys(fn ($l) => [$l['code'] => Location::create($l)]);

        $etaService = app(EtaService::class);
        $exceptionService = app(ExceptionService::class);
        $auditLogService = app(AuditLogService::class);
        $notificationService = app(NotificationService::class);

        // 4. Seed 16 Realistic Shipments (KLX-2026-0001 .. KLX-2026-0016)
        $specs = [
            ['num' => 'KLX-2026-0001', 'cust' => $customer1, 'car' => 'MAERSK', 'orig' => 'ID-JKT-HUB', 'dest' => 'NL-RTM-PORT', 'hops' => ['SG-SIN-PORT', 'MY-PKG-PORT'], 'status' => 'IN_TRANSIT', 'cargo' => 'Semiconductor Assembly Kits', 'wt' => 1450.5, 'cbm' => 6.4, 'delay_h' => 18, 'exception' => ['CUSTOMS_HOLD', 'HIGH', 'OPEN', 'Customs inspection hold at Port Klang transit terminal pending HS-code clearance.']],
            ['num' => 'KLX-2026-0002', 'cust' => $customer1, 'car' => 'KLX-AIR', 'orig' => 'ID-JKT-HUB', 'dest' => 'DE-FRA-AIR', 'hops' => ['SG-SIN-PORT'], 'status' => 'IN_TRANSIT', 'cargo' => 'Medical Cold-Chain Diagnostics', 'wt' => 320.0, 'cbm' => 1.8, 'delay_h' => 4, 'exception' => ['WEATHER_DELAY', 'MEDIUM', 'INVESTIGATING', 'Crosswind turbulence rerouting flight corridor over Bay of Bengal.']],
            ['num' => 'KLX-2026-0003', 'cust' => $customer1, 'car' => 'KLX-EXP', 'orig' => 'ID-BDG-WH', 'dest' => 'AU-SYD-PORT', 'hops' => ['ID-JKT-HUB', 'SG-SIN-PORT'], 'status' => 'DELIVERED', 'cargo' => 'Precision Robotics Components', 'wt' => 540.0, 'cbm' => 2.5, 'delay_h' => 0, 'exception' => null],
            ['num' => 'KLX-2026-0004', 'cust' => $customer2, 'car' => 'MAERSK', 'orig' => 'SG-SIN-PORT', 'dest' => 'US-LAX-PORT', 'hops' => ['MY-PKG-PORT'], 'status' => 'IN_TRANSIT', 'cargo' => 'Solar Inverter Modules', 'wt' => 4200.0, 'cbm' => 18.2, 'delay_h' => 30, 'exception' => ['PORT_CONGESTION', 'HIGH', 'OPEN', 'Berth congestion at Trans-Pacific transshipment terminal causing 30h vessel delay.']],
            ['num' => 'KLX-2026-0005', 'cust' => $customer2, 'car' => 'KLX-AIR', 'orig' => 'TH-BKK-AIR', 'dest' => 'US-JFK-AIR', 'hops' => ['DE-FRA-AIR'], 'status' => 'DELIVERED', 'cargo' => 'Optical Fiber Transceivers', 'wt' => 185.0, 'cbm' => 0.9, 'delay_h' => 0, 'exception' => ['DOCUMENTATION_ISSUE', 'LOW', 'RESOLVED', 'Commercial invoice verified and cleared by Frankfurt hub customs desk.']],
            ['num' => 'KLX-2026-0006', 'cust' => $customer1, 'car' => 'KLX-CARGO', 'orig' => 'ID-BDG-WH', 'dest' => 'ID-SUB-PORT', 'hops' => ['ID-JKT-HUB'], 'status' => 'PICKED_UP', 'cargo' => 'Industrial Packaging Rollers', 'wt' => 980.0, 'cbm' => 4.2, 'delay_h' => 0, 'exception' => null],
            ['num' => 'KLX-2026-0007', 'cust' => $customer2, 'car' => 'KLX-EXP', 'orig' => 'AU-MEL-WH', 'dest' => 'SG-SIN-PORT', 'hops' => ['AU-SYD-PORT'], 'status' => 'IN_TRANSIT', 'cargo' => 'Pharmaceutical Lab Reagents', 'wt' => 210.0, 'cbm' => 1.1, 'delay_h' => 0, 'exception' => null],
            ['num' => 'KLX-2026-0008', 'cust' => $customer1, 'car' => 'MAERSK', 'orig' => 'ID-SUB-PORT', 'dest' => 'AU-MEL-WH', 'hops' => ['AU-SYD-PORT'], 'status' => 'BOOKED', 'cargo' => 'Automotive Spare Parts', 'wt' => 2350.0, 'cbm' => 9.5, 'delay_h' => 0, 'exception' => null],
            ['num' => 'KLX-2026-0009', 'cust' => $customer2, 'car' => 'KLX-AIR', 'orig' => 'US-JFK-AIR', 'dest' => 'NL-RTM-PORT', 'hops' => ['DE-FRA-AIR'], 'status' => 'IN_TRANSIT', 'cargo' => 'Avionics Telemetry Sensors', 'wt' => 410.0, 'cbm' => 2.0, 'delay_h' => 14, 'exception' => ['WEATHER_DELAY', 'MEDIUM', 'OPEN', 'North Atlantic storm system delaying cargo uplift from JFK.']],
            ['num' => 'KLX-2026-0010', 'cust' => $customer1, 'car' => 'KLX-CARGO', 'orig' => 'ID-JKT-HUB', 'dest' => 'ID-BDG-WH', 'hops' => [], 'status' => 'CREATED', 'cargo' => 'Server Rack Enclosures', 'wt' => 640.0, 'cbm' => 3.1, 'delay_h' => 0, 'exception' => null],
            ['num' => 'KLX-2026-0011', 'cust' => $customer2, 'car' => 'MAERSK', 'orig' => 'MY-PKG-PORT', 'dest' => 'NL-RTM-PORT', 'hops' => ['SG-SIN-PORT'], 'status' => 'DELIVERED', 'cargo' => 'Natural Rubber Industrial Sheets', 'wt' => 5600.0, 'cbm' => 22.0, 'delay_h' => 0, 'exception' => null],
            ['num' => 'KLX-2026-0012', 'cust' => $customer1, 'car' => 'KLX-EXP', 'orig' => 'SG-SIN-PORT', 'dest' => 'TH-BKK-AIR', 'hops' => [], 'status' => 'PICKED_UP', 'cargo' => 'IoT Fleet Trackers', 'wt' => 95.0, 'cbm' => 0.4, 'delay_h' => 0, 'exception' => null],
            ['num' => 'KLX-2026-0013', 'cust' => $customer2, 'car' => 'KLX-AIR', 'orig' => 'DE-FRA-AIR', 'dest' => 'ID-JKT-HUB', 'hops' => ['TH-BKK-AIR'], 'status' => 'IN_TRANSIT', 'cargo' => 'CNC Milling Spindles', 'wt' => 760.0, 'cbm' => 2.8, 'delay_h' => 26, 'exception' => ['CUSTOMS_HOLD', 'HIGH', 'INVESTIGATING', 'Import permit verification requested at Jakarta Soekarno-Hatta customs desk.']],
            ['num' => 'KLX-2026-0014', 'cust' => $customer1, 'car' => 'KLX-CARGO', 'orig' => 'ID-SUB-PORT', 'dest' => 'ID-JKT-HUB', 'hops' => [], 'status' => 'DELIVERED', 'cargo' => 'Retail POS Terminals', 'wt' => 310.0, 'cbm' => 1.4, 'delay_h' => 0, 'exception' => null],
            ['num' => 'KLX-2026-0015', 'cust' => $customer2, 'car' => 'MAERSK', 'orig' => 'US-LAX-PORT', 'dest' => 'AU-SYD-PORT', 'hops' => [], 'status' => 'BOOKED', 'cargo' => 'Lithium Battery Storage Packs', 'wt' => 3100.0, 'cbm' => 11.6, 'delay_h' => 0, 'exception' => null],
            ['num' => 'KLX-2026-0016', 'cust' => $customer1, 'car' => 'KLX-AIR', 'orig' => 'TH-BKK-AIR', 'dest' => 'ID-JKT-HUB', 'hops' => ['SG-SIN-PORT'], 'status' => 'DELIVERED', 'cargo' => 'Smart Grid Power Meters', 'wt' => 420.0, 'cbm' => 1.9, 'delay_h' => 5, 'exception' => null],
        ];

        $stageSequence = ['CREATED', 'BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED'];

        foreach ($specs as $idx => $spec) {
            $origin = $locations[$spec['orig']];
            $dest = $locations[$spec['dest']];
            $carrier = $carriers[$spec['car']];
            $baseTime = now()->subDays(12 - min($idx, 9));
            $expectedArrival = $baseTime->copy()->addDays(6);
            $estimatedArrival = $spec['delay_h'] > 0
                ? $expectedArrival->copy()->addHours($spec['delay_h'])
                : $expectedArrival->copy();

            $shipment = Shipment::create([
                'shipment_number' => $spec['num'],
                'customer_id' => $spec['cust']->id,
                'carrier_id' => $carrier->id,
                'origin_location_id' => $origin->id,
                'destination_location_id' => $dest->id,
                'cargo_type' => $spec['cargo'],
                'weight_kg' => $spec['wt'],
                'volume_cbm' => $spec['cbm'],
                'expected_departure' => $baseTime->copy()->addHours(12),
                'expected_arrival' => $expectedArrival,
                'estimated_arrival' => $estimatedArrival,
                'actual_arrival' => $spec['status'] === 'DELIVERED' ? $estimatedArrival->copy() : null,
                'current_status' => $spec['status'],
                'created_at' => $baseTime,
                'updated_at' => now(),
            ]);

            $shipment->items()->create([
                'item_name' => $spec['cargo'],
                'sku' => 'SKU-'.str_replace('KLX-2026-', '', $spec['num']),
                'quantity' => ($idx % 4) + 2,
                'weight_kg' => $spec['wt'],
                'declared_value' => ($idx + 1) * 2450,
            ]);

            // Build authentic chronological checkpoints
            $targetIdx = array_search($spec['status'], $stageSequence, true);
            $eventTime = $baseTime->copy()->addHours(1);

            for ($s = 0; $s <= $targetIdx; $s++) {
                $st = $stageSequence[$s];
                $loc = match ($st) {
                    'CREATED', 'BOOKED', 'PICKED_UP' => $origin,
                    'DELIVERED' => $dest,
                    'IN_TRANSIT' => ! empty($spec['hops']) ? $locations[$spec['hops'][0]] : $origin,
                };

                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => $loc->id,
                    'created_by' => $operator->id,
                    'status' => $st,
                    'event_time' => $eventTime->copy(),
                    'latitude' => $loc->latitude,
                    'longitude' => $loc->longitude,
                    'description' => match ($st) {
                        'CREATED' => "Waybill {$shipment->shipment_number} registered at {$loc->name}.",
                        'BOOKED' => "Cargo space confirmed with {$carrier->name}.",
                        'PICKED_UP' => "Shipment collected and scanned at {$loc->city} dispatch bay.",
                        'IN_TRANSIT' => "In transit via {$loc->name} ({$loc->city}, {$loc->country}).",
                        'DELIVERED' => "Delivered and signed at {$dest->name} ({$dest->city}).",
                    },
                    'is_exception' => false,
                ]);

                // Add secondary transit hop if IN_TRANSIT or DELIVERED with multiple hops
                if ($st === 'IN_TRANSIT' && count($spec['hops']) > 1) {
                    $hop2 = $locations[$spec['hops'][1]];
                    $eventTime = $eventTime->copy()->addHours(18);
                    TrackingEvent::create([
                        'shipment_id' => $shipment->id,
                        'location_id' => $hop2->id,
                        'created_by' => $operator->id,
                        'status' => 'IN_TRANSIT',
                        'event_time' => $eventTime->copy(),
                        'latitude' => $hop2->latitude,
                        'longitude' => $hop2->longitude,
                        'description' => "Arrived at intermediate hub {$hop2->name} ({$hop2->city}).",
                        'is_exception' => false,
                    ]);
                }

                $eventTime = $eventTime->copy()->addHours(16);
            }

            // Evaluate deterministic ETA delay & create notifications
            $etaService->evaluate($shipment, $estimatedArrival, null, $operator);

            if ($spec['status'] === 'DELIVERED') {
                $notificationService->notifyShipmentCustomer(
                    $shipment,
                    "Delivered: {$shipment->shipment_number}",
                    "Your shipment {$shipment->shipment_number} arrived safely at {$dest->name}.",
                    'DELIVERED'
                );
            }

            // Create Exceptions if defined
            if ($spec['exception'] !== null) {
                [$exType, $exSev, $exStatus, $exDesc] = $spec['exception'];
                $ex = $exceptionService->openException($shipment, [
                    'exception_type' => $exType,
                    'severity' => $exSev,
                    'description' => $exDesc,
                    'detected_at' => $eventTime->copy()->subHours(2),
                ], $operator);

                if ($exStatus !== 'OPEN') {
                    $exceptionService->updateException($ex, [
                        'status' => $exStatus,
                        'resolution_notes' => $exStatus === 'RESOLVED'
                            ? 'Cleared after documentation verification and hub inspection.'
                            : 'Ops team actively coordinating with port customs officer.',
                    ], $admin);
                }
            }

            $auditLogService->log(
                $admin,
                'SHIPMENT_CREATED',
                Shipment::class,
                $shipment->id,
                null,
                ['shipment_number' => $shipment->shipment_number, 'status' => $shipment->current_status]
            );
        }
    }
}
