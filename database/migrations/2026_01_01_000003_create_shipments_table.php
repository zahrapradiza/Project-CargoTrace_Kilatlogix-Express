<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_number', 40)->unique();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('carrier_id')->constrained('carriers')->restrictOnDelete();
            $table->foreignId('origin_location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('destination_location_id')->constrained('locations')->restrictOnDelete();
            $table->string('cargo_type', 100);
            $table->decimal('weight_kg', 10, 2);
            $table->decimal('volume_cbm', 10, 3)->default(0);
            $table->dateTime('expected_departure');
            $table->dateTime('expected_arrival')->index();
            $table->dateTime('estimated_arrival')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->enum('current_status', [
                'CREATED',
                'BOOKED',
                'PICKED_UP',
                'IN_TRANSIT',
                'DELIVERED',
                'CANCELLED',
            ])->default('CREATED')->index();
            $table->boolean('is_delayed')->default(false)->index();
            $table->integer('delay_hours')->default(0);
            $table->enum('delay_severity', ['NONE', 'LOW', 'MEDIUM', 'HIGH'])->default('NONE')->index();
            $table->string('delay_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'current_status']);
            $table->index(['carrier_id', 'current_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
