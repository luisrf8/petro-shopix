<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_order_details')) {
            return;
        }

        Schema::table('sales_order_details', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_order_details', 'is_custom_item')) {
                $table->boolean('is_custom_item')->default(false)->after('product_variant_id');
            }

            if (!Schema::hasColumn('sales_order_details', 'custom_product_name')) {
                $table->string('custom_product_name', 255)->nullable()->after('is_custom_item');
            }

            if (!Schema::hasColumn('sales_order_details', 'custom_variant_code')) {
                $table->string('custom_variant_code', 120)->nullable()->after('custom_product_name');
            }

            if (!Schema::hasColumn('sales_order_details', 'custom_variant_label')) {
                $table->string('custom_variant_label', 120)->nullable()->after('custom_variant_code');
            }

            if (!Schema::hasColumn('sales_order_details', 'custom_unit_type')) {
                $table->string('custom_unit_type', 30)->nullable()->after('custom_variant_label');
            }

            if (!Schema::hasColumn('sales_order_details', 'custom_quantity_input_mode')) {
                $table->string('custom_quantity_input_mode', 20)->nullable()->after('custom_unit_type');
            }

            if (!Schema::hasColumn('sales_order_details', 'custom_min_sale_quantity')) {
                $table->decimal('custom_min_sale_quantity', 12, 2)->nullable()->after('custom_quantity_input_mode');
            }

            if (!Schema::hasColumn('sales_order_details', 'custom_purchase_unit_price')) {
                $table->decimal('custom_purchase_unit_price', 12, 2)->nullable()->after('custom_min_sale_quantity');
            }

            if (!Schema::hasColumn('sales_order_details', 'custom_description')) {
                $table->text('custom_description')->nullable()->after('custom_purchase_unit_price');
            }
        });

        if (Schema::hasColumn('sales_order_details', 'product_variant_id')) {
            try {
                DB::statement('ALTER TABLE sales_order_details DROP FOREIGN KEY sales_order_details_product_variant_id_foreign');
            } catch (\Throwable $exception) {
            }

            DB::statement('ALTER TABLE sales_order_details MODIFY product_variant_id BIGINT UNSIGNED NULL');

            try {
                DB::statement('ALTER TABLE sales_order_details ADD CONSTRAINT sales_order_details_product_variant_id_foreign FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE SET NULL');
            } catch (\Throwable $exception) {
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sales_order_details')) {
            return;
        }

        Schema::table('sales_order_details', function (Blueprint $table) {
            foreach ([
                'custom_description',
                'custom_purchase_unit_price',
                'custom_min_sale_quantity',
                'custom_quantity_input_mode',
                'custom_unit_type',
                'custom_variant_label',
                'custom_variant_code',
                'custom_product_name',
                'is_custom_item',
            ] as $column) {
                if (Schema::hasColumn('sales_order_details', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
