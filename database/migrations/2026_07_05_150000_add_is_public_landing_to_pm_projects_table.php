<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pm_projects')) {
            Schema::table('pm_projects', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_projects', 'is_public_landing')) {
                    $table->boolean('is_public_landing')->default(true)->after('phase');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pm_projects') && Schema::hasColumn('pm_projects', 'is_public_landing')) {
            Schema::table('pm_projects', function (Blueprint $table) {
                $table->dropColumn('is_public_landing');
            });
        }
    }
};