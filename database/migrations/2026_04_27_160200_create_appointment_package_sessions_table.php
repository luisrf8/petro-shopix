<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_package_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('appointment_package_id')->constrained('appointment_packages')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->unsignedInteger('session_number');
            $table->dateTime('scheduled_for')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();
            $table->index(['tenant_id', 'appointment_package_id', 'status'], 'appointment_package_session_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_package_sessions');
    }
};
