<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pm_project_tasks')) {
            Schema::table('pm_project_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_project_tasks', 'responsible_team_member_id')) {
                    $table->foreignId('responsible_team_member_id')->nullable()->after('project_id')->constrained('pm_team_members')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('pm_project_assignments')) {
            Schema::table('pm_project_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_project_assignments', 'member_status')) {
                    $table->string('member_status', 30)->default('active')->after('project_share_percent');
                }
            });
        }

        if (Schema::hasTable('pm_team_members')) {
            Schema::table('pm_team_members', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_team_members', 'payment_frequency')) {
                    $table->string('payment_frequency', 30)->default('monthly')->after('role');
                }
            });
        }

        if (Schema::hasTable('pm_quotation_items')) {
            Schema::table('pm_quotation_items', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_quotation_items', 'item_type')) {
                    $table->string('item_type', 30)->default('product')->after('product_variant_id');
                }
            });
        }

        if (!Schema::hasTable('pm_project_assets')) {
            Schema::create('pm_project_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('project_id')->constrained('pm_projects')->cascadeOnDelete();
                $table->foreignId('task_id')->nullable()->constrained('pm_project_tasks')->nullOnDelete();
                $table->string('asset_type', 40);
                $table->string('title', 255)->nullable();
                $table->text('notes')->nullable();
                $table->string('file_path', 400)->nullable();
                $table->decimal('amount', 14, 4)->nullable();
                $table->string('currency_code', 3)->nullable();
                $table->date('happened_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'asset_type']);
                $table->index(['tenant_id', 'happened_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pm_project_assets');

        if (Schema::hasTable('pm_quotation_items')) {
            Schema::table('pm_quotation_items', function (Blueprint $table) {
                if (Schema::hasColumn('pm_quotation_items', 'item_type')) {
                    $table->dropColumn('item_type');
                }
            });
        }

        if (Schema::hasTable('pm_team_members')) {
            Schema::table('pm_team_members', function (Blueprint $table) {
                if (Schema::hasColumn('pm_team_members', 'payment_frequency')) {
                    $table->dropColumn('payment_frequency');
                }
            });
        }

        if (Schema::hasTable('pm_project_assignments')) {
            Schema::table('pm_project_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('pm_project_assignments', 'member_status')) {
                    $table->dropColumn('member_status');
                }
            });
        }

        if (Schema::hasTable('pm_project_tasks')) {
            Schema::table('pm_project_tasks', function (Blueprint $table) {
                if (Schema::hasColumn('pm_project_tasks', 'responsible_team_member_id')) {
                    $table->dropConstrainedForeignId('responsible_team_member_id');
                }
            });
        }
    }
};
