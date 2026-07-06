<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('pm_project_assets')) {
            return;
        }

        Schema::table('pm_project_assets', function (Blueprint $table) {
            if (!Schema::hasColumn('pm_project_assets', 'phase')) {
                $table->string('phase', 30)->nullable()->after('task_id');
                $table->index(['tenant_id', 'phase']);
            }
        });

        $projectPhases = DB::table('pm_projects')->pluck('phase', 'id');

        DB::table('pm_project_assets')
            ->select(['id', 'project_id'])
            ->orderBy('id')
            ->chunkById(200, function ($assets) use ($projectPhases) {
                foreach ($assets as $asset) {
                    DB::table('pm_project_assets')
                        ->where('id', $asset->id)
                        ->update([
                            'phase' => $projectPhases[$asset->project_id] ?? 'inicio',
                        ]);
                }
            });

        DB::table('pm_project_assets')
            ->whereNull('phase')
            ->update(['phase' => 'inicio']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('pm_project_assets')) {
            return;
        }

        Schema::table('pm_project_assets', function (Blueprint $table) {
            if (Schema::hasColumn('pm_project_assets', 'phase')) {
                $table->dropIndex('pm_project_assets_tenant_id_phase_index');
                $table->dropColumn('phase');
            }
        });
    }
};
