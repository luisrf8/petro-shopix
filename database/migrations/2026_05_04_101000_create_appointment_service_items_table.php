<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_service_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('appointment_service_id')->constrained('appointment_services')->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(1);
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->unsignedInteger('buffer_minutes')->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'appointment_id'], 'appointment_service_item_lookup_idx');
            $table->index(['tenant_id', 'appointment_service_id'], 'appointment_service_item_service_idx');
            $table->unique(['appointment_id', 'sequence'], 'appointment_service_item_sequence_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_service_items');
    }
};
