<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_methods')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                if (!Schema::hasColumn('payment_methods', 'applies_igtf_base')) {
                    $table->boolean('applies_igtf_base')->nullable()->after('has_reference');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'amount_original')) {
                    $table->decimal('amount_original', 12, 2)->nullable()->after('amount');
                }
                if (!Schema::hasColumn('payments', 'amount_base')) {
                    $table->decimal('amount_base', 12, 2)->nullable()->after('amount_original');
                }
                if (!Schema::hasColumn('payments', 'exchange_rate_to_base')) {
                    $table->decimal('exchange_rate_to_base', 16, 6)->nullable()->after('amount_base');
                }
                if (!Schema::hasColumn('payments', 'applies_igtf')) {
                    $table->boolean('applies_igtf')->nullable()->after('exchange_rate_to_base');
                }
            });
        }

        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_orders', 'total_paid_base')) {
                    $table->decimal('total_paid_base', 12, 2)->nullable()->after('total_discount');
                }
                if (!Schema::hasColumn('sales_orders', 'igtf_base_amount')) {
                    $table->decimal('igtf_base_amount', 12, 2)->nullable()->after('total_paid_base');
                }
                if (!Schema::hasColumn('sales_orders', 'igtf_amount')) {
                    $table->decimal('igtf_amount', 12, 2)->nullable()->after('igtf_base_amount');
                }
                if (!Schema::hasColumn('sales_orders', 'change_due_base')) {
                    $table->decimal('change_due_base', 12, 2)->nullable()->after('igtf_amount');
                }
                if (!Schema::hasColumn('sales_orders', 'change_paid_in_bs')) {
                    $table->boolean('change_paid_in_bs')->default(false)->after('change_due_base');
                }
                if (!Schema::hasColumn('sales_orders', 'change_rate_to_bs')) {
                    $table->decimal('change_rate_to_bs', 16, 6)->nullable()->after('change_paid_in_bs');
                }
                if (!Schema::hasColumn('sales_orders', 'change_due_bs')) {
                    $table->decimal('change_due_bs', 12, 2)->nullable()->after('change_rate_to_bs');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_orders')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                foreach (['change_due_bs', 'change_rate_to_bs', 'change_paid_in_bs', 'change_due_base', 'igtf_amount', 'igtf_base_amount', 'total_paid_base'] as $column) {
                    if (Schema::hasColumn('sales_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                foreach (['applies_igtf', 'exchange_rate_to_base', 'amount_base', 'amount_original'] as $column) {
                    if (Schema::hasColumn('payments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('payment_methods') && Schema::hasColumn('payment_methods', 'applies_igtf_base')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropColumn('applies_igtf_base');
            });
        }
    }
};
