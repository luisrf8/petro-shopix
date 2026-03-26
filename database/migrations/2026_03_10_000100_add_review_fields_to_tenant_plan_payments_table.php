<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenant_plan_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('tenant_plan_payments', 'payment_reference')) {
                $table->string('payment_reference')->nullable();
            }

            if (!Schema::hasColumn('tenant_plan_payments', 'payment_proof')) {
                $table->string('payment_proof')->nullable();
            }

            if (!Schema::hasColumn('tenant_plan_payments', 'review_notes')) {
                $table->text('review_notes')->nullable();
            }

            if (!Schema::hasColumn('tenant_plan_payments', 'reviewed_at')) {
                $table->dateTime('reviewed_at')->nullable();
            }

            if (!Schema::hasColumn('tenant_plan_payments', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenant_plan_payments', function (Blueprint $table) {
            $columns = [];

            foreach (['payment_reference', 'payment_proof', 'review_notes', 'reviewed_at', 'reviewed_by'] as $column) {
                if (Schema::hasColumn('tenant_plan_payments', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
