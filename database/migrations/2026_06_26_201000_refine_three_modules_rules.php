<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pm_quotations')) {
            Schema::table('pm_quotations', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_quotations', 'customer_id')) {
                    $table->foreignId('customer_id')->nullable()->after('title')->constrained('users')->nullOnDelete();
                }

                if (!Schema::hasColumn('pm_quotations', 'converted_purchase_order_id')) {
                    $table->foreignId('converted_purchase_order_id')->nullable()->after('converted_project_id')->constrained('purchase_orders')->nullOnDelete();
                }

                if (!Schema::hasColumn('pm_quotations', 'converted_to_inventory_at')) {
                    $table->dateTime('converted_to_inventory_at')->nullable()->after('converted_to_project_at');
                }
            });
        }

        if (Schema::hasTable('pm_project_assignments')) {
            Schema::table('pm_project_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_project_assignments', 'pay_amount')) {
                    $table->decimal('pay_amount', 14, 4)->default(0)->after('commission_value');
                }

                if (!Schema::hasColumn('pm_project_assignments', 'pay_currency_code')) {
                    $table->string('pay_currency_code', 3)->default('USD')->after('pay_amount');
                }

                if (!Schema::hasColumn('pm_project_assignments', 'project_share_percent')) {
                    $table->decimal('project_share_percent', 7, 4)->default(0)->after('pay_currency_code');
                }
            });
        }

        if (Schema::hasTable('pm_team_members')) {
            Schema::table('pm_team_members', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_team_members', 'terminated_at')) {
                    $table->dateTime('terminated_at')->nullable()->after('is_active');
                }

                if (!Schema::hasColumn('pm_team_members', 'termination_reason')) {
                    $table->string('termination_reason', 255)->nullable()->after('terminated_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pm_team_members')) {
            Schema::table('pm_team_members', function (Blueprint $table) {
                if (Schema::hasColumn('pm_team_members', 'termination_reason')) {
                    $table->dropColumn('termination_reason');
                }

                if (Schema::hasColumn('pm_team_members', 'terminated_at')) {
                    $table->dropColumn('terminated_at');
                }
            });
        }

        if (Schema::hasTable('pm_project_assignments')) {
            Schema::table('pm_project_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('pm_project_assignments', 'project_share_percent')) {
                    $table->dropColumn('project_share_percent');
                }

                if (Schema::hasColumn('pm_project_assignments', 'pay_currency_code')) {
                    $table->dropColumn('pay_currency_code');
                }

                if (Schema::hasColumn('pm_project_assignments', 'pay_amount')) {
                    $table->dropColumn('pay_amount');
                }
            });
        }

        if (Schema::hasTable('pm_quotations')) {
            Schema::table('pm_quotations', function (Blueprint $table) {
                if (Schema::hasColumn('pm_quotations', 'converted_to_inventory_at')) {
                    $table->dropColumn('converted_to_inventory_at');
                }

                if (Schema::hasColumn('pm_quotations', 'converted_purchase_order_id')) {
                    $table->dropConstrainedForeignId('converted_purchase_order_id');
                }

                if (Schema::hasColumn('pm_quotations', 'customer_id')) {
                    $table->dropConstrainedForeignId('customer_id');
                }
            });
        }
    }
};
