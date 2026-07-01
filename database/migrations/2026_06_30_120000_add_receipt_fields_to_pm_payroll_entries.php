<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pm_payroll_entries')) {
            return;
        }

        Schema::table('pm_payroll_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('pm_payroll_entries', 'payment_reason')) {
                $table->text('payment_reason')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('pm_payroll_entries', 'deduction_reason')) {
                $table->text('deduction_reason')->nullable()->after('payment_reason');
            }

            if (!Schema::hasColumn('pm_payroll_entries', 'total_to_pay')) {
                $table->decimal('total_to_pay', 14, 4)->nullable()->after('deduction_reason');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pm_payroll_entries')) {
            return;
        }

        Schema::table('pm_payroll_entries', function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn('pm_payroll_entries', 'payment_reason')) {
                $drops[] = 'payment_reason';
            }

            if (Schema::hasColumn('pm_payroll_entries', 'deduction_reason')) {
                $drops[] = 'deduction_reason';
            }

            if (Schema::hasColumn('pm_payroll_entries', 'total_to_pay')) {
                $drops[] = 'total_to_pay';
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
