<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'special_taxpayer')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->boolean('special_taxpayer')->default(false)->after('electronic_invoicing_enabled');
            });
        }

        if (Schema::hasTable('dollar_rates') && !Schema::hasColumn('dollar_rates', 'tenant_id')) {
            Schema::table('dollar_rates', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('rate');
                $table->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dollar_rates') && Schema::hasColumn('dollar_rates', 'tenant_id')) {
            Schema::table('dollar_rates', function (Blueprint $table) {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'special_taxpayer')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('special_taxpayer');
            });
        }
    }
};