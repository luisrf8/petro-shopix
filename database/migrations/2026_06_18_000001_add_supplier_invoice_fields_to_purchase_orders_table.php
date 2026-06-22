<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'provider_rif')) {
                $table->string('provider_rif', 30)->nullable()->after('provider_name');
            }

            if (!Schema::hasColumn('purchase_orders', 'supplier_invoice_number')) {
                $table->string('supplier_invoice_number', 60)->nullable()->after('entry_mode');
            }

            if (!Schema::hasColumn('purchase_orders', 'supplier_invoice_control_number')) {
                $table->string('supplier_invoice_control_number', 60)->nullable()->after('supplier_invoice_number');
            }

            if (!Schema::hasColumn('purchase_orders', 'supplier_invoice_date')) {
                $table->date('supplier_invoice_date')->nullable()->after('supplier_invoice_control_number');
            }

            if (!Schema::hasColumn('purchase_orders', 'supplier_invoice_file_path')) {
                $table->string('supplier_invoice_file_path')->nullable()->after('supplier_invoice_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            foreach (['supplier_invoice_file_path', 'supplier_invoice_date', 'supplier_invoice_control_number', 'supplier_invoice_number', 'provider_rif'] as $column) {
                if (Schema::hasColumn('purchase_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};