<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tenant_plan_payments')) {
            return;
        }

        Schema::table('tenant_plan_payments', function (Blueprint $table) {
            $table->foreign('plan_id')
                ->references('id')
                ->on('plans')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenant_plan_payments')) {
            return;
        }

        Schema::table('tenant_plan_payments', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
        });
    }
};
