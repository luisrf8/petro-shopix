<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accounts_payables', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts_payables', 'invoice_number')) {
                $table->string('invoice_number', 120)->nullable()->after('document_number');
            }

            if (!Schema::hasColumn('accounts_payables', 'control_number')) {
                $table->string('control_number', 120)->nullable()->after('invoice_number');
            }

            if (!Schema::hasColumn('accounts_payables', 'invoice_date')) {
                $table->date('invoice_date')->nullable()->after('control_number');
            }
        });

        Schema::table('islr_withholdings', function (Blueprint $table) {
            if (!Schema::hasColumn('islr_withholdings', 'invoice_number')) {
                $table->string('invoice_number', 120)->nullable()->after('certificate_number');
            }

            if (!Schema::hasColumn('islr_withholdings', 'control_number')) {
                $table->string('control_number', 120)->nullable()->after('invoice_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('islr_withholdings', function (Blueprint $table) {
            foreach (['control_number', 'invoice_number'] as $column) {
                if (Schema::hasColumn('islr_withholdings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('accounts_payables', function (Blueprint $table) {
            foreach (['invoice_date', 'control_number', 'invoice_number'] as $column) {
                if (Schema::hasColumn('accounts_payables', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};