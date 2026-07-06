<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'background_media_type')) {
                $table->string('background_media_type', 20)->default('image')->after('background_image');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants') || !Schema::hasColumn('tenants', 'background_media_type')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('background_media_type');
        });
    }
};
