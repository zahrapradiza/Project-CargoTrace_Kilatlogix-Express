<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->string('item_name');
            $table->string('sku', 80);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('weight_kg', 10, 2);
            $table->decimal('declared_value', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['shipment_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};

