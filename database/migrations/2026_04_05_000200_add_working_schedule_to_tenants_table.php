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
            if (!Schema::hasColumn('tenants', 'working_days')) {
                $table->json('working_days')->nullable()->after('phone_number');
            }

            if (!Schema::hasColumn('tenants', 'opening_time')) {
                $table->time('opening_time')->nullable()->after('working_days');
            }

            if (!Schema::hasColumn('tenants', 'closing_time')) {
                $table->time('closing_time')->nullable()->after('opening_time');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'closing_time')) {
                $table->dropColumn('closing_time');
            }

            if (Schema::hasColumn('tenants', 'opening_time')) {
                $table->dropColumn('opening_time');
            }

            if (Schema::hasColumn('tenants', 'working_days')) {
                $table->dropColumn('working_days');
            }
        });
    }
};
