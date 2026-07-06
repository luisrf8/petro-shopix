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
            if (!Schema::hasColumn('tenants', 'rif')) {
                $table->string('rif', 20)->nullable()->after('email');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants') || !Schema::hasColumn('tenants', 'rif')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('rif');
        });
    }
};