<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pm_payroll_entry_items')) {
            return;
        }

        Schema::create('pm_payroll_entry_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('payroll_entry_id')->constrained('pm_payroll_entries')->cascadeOnDelete();
            $table->string('item_type', 20); // payment | deduction
            $table->decimal('amount', 14, 4);
            $table->string('description', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'payroll_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_payroll_entry_items');
    }
};
