<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('euro_rates')) {
            Schema::create('euro_rates', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->decimal('rate', 10, 4);
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('euro_rates');
    }
};
