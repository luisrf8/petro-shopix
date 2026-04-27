<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('appointment_service_id')->constrained('appointment_services')->cascadeOnDelete();
            $table->unsignedInteger('sessions_count')->default(1);
            $table->unsignedInteger('repeat_every_weeks')->default(1);
            $table->unsignedTinyInteger('preferred_day_of_week')->nullable();
            $table->time('preferred_time')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_packages');
    }
};
