<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('amount_original', 12, 4)->default(0);
            $table->decimal('exchange_rate_to_bs', 12, 4)->default(1);
            $table->decimal('amount_bs', 12, 2)->default(0);
            $table->date('spent_at');
            $table->string('payment_method')->nullable();
            $table->string('provider_name')->nullable();
            $table->string('status')->default('paid');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'spent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_expenses');
    }
};