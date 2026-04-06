<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('material_package_items')) {
            return;
        }

        Schema::table('material_package_items', function (Blueprint $table) {
            if (!Schema::hasColumn('material_package_items', 'selection_mode')) {
                $table->string('selection_mode', 20)->default('variant')->after('quantity');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('material_package_items')) {
            return;
        }

        Schema::table('material_package_items', function (Blueprint $table) {
            if (Schema::hasColumn('material_package_items', 'selection_mode')) {
                $table->dropColumn('selection_mode');
            }
        });
    }
};
