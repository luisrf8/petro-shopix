<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('image_path', 500);
            $table->string('caption', 255)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'appointment_id'], 'appointment_images_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_images');
    }
};
