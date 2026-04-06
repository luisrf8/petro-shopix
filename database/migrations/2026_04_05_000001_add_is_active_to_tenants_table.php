<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenants') || Schema::hasColumn('tenants', 'is_active')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('email');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants') || !Schema::hasColumn('tenants', 'is_active')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
