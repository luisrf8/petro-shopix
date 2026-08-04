<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pm_team_groups')) {
            Schema::create('pm_team_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name', 120);
                $table->string('payment_type', 30)->default('fixed');
                $table->string('default_payment_frequency', 30)->default('monthly');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('description', 255)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'is_active']);
            });
        }

        if (Schema::hasTable('pm_team_members') && !Schema::hasColumn('pm_team_members', 'team_group_id')) {
            Schema::table('pm_team_members', function (Blueprint $table) {
                $table->foreignId('team_group_id')->nullable()->after('tenant_id')->constrained('pm_team_groups')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pm_team_members') && Schema::hasColumn('pm_team_members', 'team_group_id')) {
            Schema::table('pm_team_members', function (Blueprint $table) {
                $table->dropConstrainedForeignId('team_group_id');
            });
        }

        Schema::dropIfExists('pm_team_groups');
    }
};
