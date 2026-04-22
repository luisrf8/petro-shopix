<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'delivery_enabled')) {
                $table->boolean('delivery_enabled')->default(false)->after('restrict_delivery_city_to_tenant');
            }

            if (!Schema::hasColumn('tenants', 'delivery_fee_mode')) {
                $table->string('delivery_fee_mode', 20)->default('free')->after('delivery_enabled');
            }

            if (!Schema::hasColumn('tenants', 'delivery_fixed_fee')) {
                $table->decimal('delivery_fixed_fee', 10, 2)->default(0)->after('delivery_fee_mode');
            }

            if (!Schema::hasColumn('tenants', 'delivery_fee_per_km')) {
                $table->decimal('delivery_fee_per_km', 10, 2)->default(0)->after('delivery_fixed_fee');
            }

            if (!Schema::hasColumn('tenants', 'delivery_notifications_enabled')) {
                $table->boolean('delivery_notifications_enabled')->default(true)->after('delivery_fee_per_km');
            }
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'delivery_fee')) {
                $table->decimal('delivery_fee', 10, 2)->default(0)->after('sale_currency_code');
            }

            if (!Schema::hasColumn('sales_orders', 'delivery_fee_mode')) {
                $table->string('delivery_fee_mode', 20)->nullable()->after('delivery_fee');
            }

            if (!Schema::hasColumn('sales_orders', 'delivery_distance_km')) {
                $table->decimal('delivery_distance_km', 8, 2)->nullable()->after('delivery_fee_mode');
            }

            if (!Schema::hasColumn('sales_orders', 'delivery_assigned_user_id')) {
                $table->unsignedBigInteger('delivery_assigned_user_id')->nullable()->after('delivery_distance_km');
                $table->foreign('delivery_assigned_user_id')->references('id')->on('users')->nullOnDelete();
            }
        });

        DB::table('roles')->updateOrInsert(
            ['name' => 'delivery'],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'delivery_assigned_user_id')) {
                $table->dropForeign(['delivery_assigned_user_id']);
                $table->dropColumn('delivery_assigned_user_id');
            }

            $columns = array_filter([
                Schema::hasColumn('sales_orders', 'delivery_distance_km') ? 'delivery_distance_km' : null,
                Schema::hasColumn('sales_orders', 'delivery_fee_mode') ? 'delivery_fee_mode' : null,
                Schema::hasColumn('sales_orders', 'delivery_fee') ? 'delivery_fee' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('tenants', 'delivery_notifications_enabled') ? 'delivery_notifications_enabled' : null,
                Schema::hasColumn('tenants', 'delivery_fee_per_km') ? 'delivery_fee_per_km' : null,
                Schema::hasColumn('tenants', 'delivery_fixed_fee') ? 'delivery_fixed_fee' : null,
                Schema::hasColumn('tenants', 'delivery_fee_mode') ? 'delivery_fee_mode' : null,
                Schema::hasColumn('tenants', 'delivery_enabled') ? 'delivery_enabled' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        DB::table('roles')->where('name', 'delivery')->delete();
    }
};