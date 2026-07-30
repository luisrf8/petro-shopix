<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'logo_chip_background')) {
                $table->string('logo_chip_background', 20)
                    ->default('white')
                    ->after('color_accent');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants') || !Schema::hasColumn('tenants', 'logo_chip_background')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('logo_chip_background');
        });
    }
};
