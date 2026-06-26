<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pm_project_tasks')) {
            Schema::create('pm_project_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('project_id')->constrained('pm_projects')->cascadeOnDelete();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('status', 30)->default('todo');
                $table->date('due_date')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'due_date']);
            });
        }

        if (!Schema::hasTable('pm_project_assignments')) {
            Schema::create('pm_project_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->foreignId('project_id')->constrained('pm_projects')->cascadeOnDelete();
                $table->foreignId('team_member_id')->constrained('pm_team_members')->cascadeOnDelete();
                $table->string('commission_type', 20)->default('none');
                $table->decimal('commission_value', 14, 4)->default(0);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['project_id', 'team_member_id']);
                $table->index(['tenant_id', 'commission_type']);
            });
        }

        if (Schema::hasTable('pm_quotations')) {
            Schema::table('pm_quotations', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_quotations', 'quotation_kind')) {
                    $table->string('quotation_kind', 30)->default('mixed')->after('type');
                }

                if (!Schema::hasColumn('pm_quotations', 'conversion_target')) {
                    $table->string('conversion_target', 30)->nullable()->after('notes');
                }

                if (!Schema::hasColumn('pm_quotations', 'converted_sale_reference')) {
                    $table->string('converted_sale_reference', 120)->nullable()->after('conversion_target');
                }

                if (!Schema::hasColumn('pm_quotations', 'converted_project_id')) {
                    $table->foreignId('converted_project_id')->nullable()->after('converted_sale_reference')->constrained('pm_projects')->nullOnDelete();
                }

                if (!Schema::hasColumn('pm_quotations', 'converted_to_sale_at')) {
                    $table->dateTime('converted_to_sale_at')->nullable()->after('converted_project_id');
                }

                if (!Schema::hasColumn('pm_quotations', 'converted_to_project_at')) {
                    $table->dateTime('converted_to_project_at')->nullable()->after('converted_to_sale_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pm_quotations')) {
            Schema::table('pm_quotations', function (Blueprint $table) {
                if (Schema::hasColumn('pm_quotations', 'quotation_kind')) {
                    $table->dropColumn('quotation_kind');
                }

                if (Schema::hasColumn('pm_quotations', 'converted_to_project_at')) {
                    $table->dropColumn('converted_to_project_at');
                }

                if (Schema::hasColumn('pm_quotations', 'converted_to_sale_at')) {
                    $table->dropColumn('converted_to_sale_at');
                }

                if (Schema::hasColumn('pm_quotations', 'converted_project_id')) {
                    $table->dropConstrainedForeignId('converted_project_id');
                }

                if (Schema::hasColumn('pm_quotations', 'converted_sale_reference')) {
                    $table->dropColumn('converted_sale_reference');
                }

                if (Schema::hasColumn('pm_quotations', 'conversion_target')) {
                    $table->dropColumn('conversion_target');
                }
            });
        }

        Schema::dropIfExists('pm_project_assignments');
        Schema::dropIfExists('pm_project_tasks');
    }
};
