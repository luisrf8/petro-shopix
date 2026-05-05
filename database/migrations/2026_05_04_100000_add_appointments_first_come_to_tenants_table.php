<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'appointments_first_come_enabled')) {
                $table->boolean('appointments_first_come_enabled')->default(false)->after('working_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'appointments_first_come_enabled')) {
                $table->dropColumn('appointments_first_come_enabled');
            }
        });
    }
};
