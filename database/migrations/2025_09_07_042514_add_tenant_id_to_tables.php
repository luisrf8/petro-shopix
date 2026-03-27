<?php

// database/migrations/2025_09_04_000001_add_tenant_id_to_tables.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $tables = [
            'users',
            'products',
            'currencies',
            'dollar_rates',
            'payment_methods',
            'payment_images',
            'product_images',
            'product_variants',
            'sales_orders',
            'sales_order_details',
            'sales_returns',
            'sales_return_items',
            'purchase_orders',
            'purchase_order_detail',
            'payments',
            'payment_images',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'products',
            'currencies',
            'dollar_rates',
            'payment_methods',
            'payment_images',
            'product_images',
            'product_variants',
            'sales_orders',
            'sales_order_details',
            'sales_returns',
            'sales_return_items',
            'purchase_orders',
            'purchase_order_detail',
            'payments',
            'payment_images',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'tenant_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }
    }
};
