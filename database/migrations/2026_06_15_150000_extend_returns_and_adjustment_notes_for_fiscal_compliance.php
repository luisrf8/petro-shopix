<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants')) {
            Schema::table('tenants', function (Blueprint $table) {
                if (!Schema::hasColumn('tenants', 'credit_note_max_age_days')) {
                    $table->unsignedInteger('credit_note_max_age_days')->default(30)->after('printer_tax_change_reference');
                }
            });
        }

        if (Schema::hasTable('sales_adjustment_notes')) {
            Schema::table('sales_adjustment_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_adjustment_notes', 'adjustment_mode')) {
                    $table->string('adjustment_mode', 40)->nullable()->after('note_type');
                }
                if (!Schema::hasColumn('sales_adjustment_notes', 'taxable_base')) {
                    $table->decimal('taxable_base', 12, 2)->nullable()->after('amount');
                }
                if (!Schema::hasColumn('sales_adjustment_notes', 'tax_rate')) {
                    $table->decimal('tax_rate', 8, 4)->nullable()->after('taxable_base');
                }
                if (!Schema::hasColumn('sales_adjustment_notes', 'tax_amount')) {
                    $table->decimal('tax_amount', 12, 2)->nullable()->after('tax_rate');
                }
                if (!Schema::hasColumn('sales_adjustment_notes', 'affected_igtf_amount')) {
                    $table->decimal('affected_igtf_amount', 12, 2)->nullable()->after('tax_amount');
                }
            });
        }

        if (Schema::hasTable('sales_returns')) {
            Schema::table('sales_returns', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_returns', 'subtotal_returned')) {
                    $table->decimal('subtotal_returned', 12, 2)->default(0)->after('reason');
                }
                if (!Schema::hasColumn('sales_returns', 'tax_returned')) {
                    $table->decimal('tax_returned', 12, 2)->default(0)->after('subtotal_returned');
                }
                if (!Schema::hasColumn('sales_returns', 'igtf_returned')) {
                    $table->decimal('igtf_returned', 12, 2)->default(0)->after('tax_returned');
                }
                if (!Schema::hasColumn('sales_returns', 'total_returned')) {
                    $table->decimal('total_returned', 12, 2)->default(0)->after('igtf_returned');
                }
            });
        }

        if (Schema::hasTable('sales_return_items')) {
            Schema::table('sales_return_items', function (Blueprint $table) {
                if (!Schema::hasColumn('sales_return_items', 'disposition')) {
                    $table->string('disposition', 30)->default('resalable')->after('quantity');
                }
                if (!Schema::hasColumn('sales_return_items', 'returned_subtotal')) {
                    $table->decimal('returned_subtotal', 12, 2)->default(0)->after('price');
                }
                if (!Schema::hasColumn('sales_return_items', 'returned_tax_amount')) {
                    $table->decimal('returned_tax_amount', 12, 2)->default(0)->after('returned_subtotal');
                }
                if (!Schema::hasColumn('sales_return_items', 'returned_igtf_amount')) {
                    $table->decimal('returned_igtf_amount', 12, 2)->default(0)->after('returned_tax_amount');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_return_items')) {
            Schema::table('sales_return_items', function (Blueprint $table) {
                foreach (['returned_igtf_amount', 'returned_tax_amount', 'returned_subtotal', 'disposition'] as $column) {
                    if (Schema::hasColumn('sales_return_items', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('sales_returns')) {
            Schema::table('sales_returns', function (Blueprint $table) {
                foreach (['total_returned', 'igtf_returned', 'tax_returned', 'subtotal_returned'] as $column) {
                    if (Schema::hasColumn('sales_returns', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('sales_adjustment_notes')) {
            Schema::table('sales_adjustment_notes', function (Blueprint $table) {
                foreach (['affected_igtf_amount', 'tax_amount', 'tax_rate', 'taxable_base', 'adjustment_mode'] as $column) {
                    if (Schema::hasColumn('sales_adjustment_notes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'credit_note_max_age_days')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('credit_note_max_age_days');
            });
        }
    }
};
