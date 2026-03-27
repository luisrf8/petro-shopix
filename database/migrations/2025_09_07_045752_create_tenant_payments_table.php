<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tenant_plan_payments')) {
            return;
        }

        Schema::create('tenant_plan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2); // Monto pagado
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->dateTime('paid_at')->nullable(); // Fecha de pago
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_plan_payments');
    }
};
