<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_orders', 'subtotal_before_discount')) {
                    $table->decimal('subtotal_before_discount', 12, 2)->default(0)->after('sale_currency_code');
                }

                if (!Schema::hasColumn('sales_orders', 'total_discount')) {
                    $table->decimal('total_discount', 12, 2)->default(0)->after('subtotal_before_discount');
                }
            });
        }

        if (Schema::hasTable('sales_order_details')) {
            Schema::table('sales_order_details', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_order_details', 'line_subtotal_before_discount')) {
                    $table->decimal('line_subtotal_before_discount', 12, 2)->default(0)->after('amount');
                }

                if (!Schema::hasColumn('sales_order_details', 'line_discount_amount')) {
                    $table->decimal('line_discount_amount', 12, 2)->default(0)->after('line_subtotal_before_discount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_order_details')) {
            Schema::table('sales_order_details', function (Blueprint $table) {
                if (Schema::hasColumn('sales_order_details', 'line_discount_amount')) {
                    $table->dropColumn('line_discount_amount');
                }

                if (Schema::hasColumn('sales_order_details', 'line_subtotal_before_discount')) {
                    $table->dropColumn('line_subtotal_before_discount');
                }
            });
        }

        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (Schema::hasColumn('sales_orders', 'total_discount')) {
                    $table->dropColumn('total_discount');
                }

                if (Schema::hasColumn('sales_orders', 'subtotal_before_discount')) {
                    $table->dropColumn('subtotal_before_discount');
                }
            });
        }
    }
};
