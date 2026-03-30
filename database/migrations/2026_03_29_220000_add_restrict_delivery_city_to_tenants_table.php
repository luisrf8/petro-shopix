<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'restrict_delivery_city_to_tenant')) {
                $table->boolean('restrict_delivery_city_to_tenant')->default(true)->after('electronic_invoicing_enabled');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'restrict_delivery_city_to_tenant')) {
                $table->dropColumn('restrict_delivery_city_to_tenant');
            }
        });
    }
};
