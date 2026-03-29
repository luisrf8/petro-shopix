<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tenant_plan_payments', 'expires_at')) {
            Schema::table('tenant_plan_payments', function (Blueprint $table) {
                $table->dateTime('expires_at')->nullable()->after('paid_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tenant_plan_payments', 'expires_at')) {
            Schema::table('tenant_plan_payments', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }
    }
};
