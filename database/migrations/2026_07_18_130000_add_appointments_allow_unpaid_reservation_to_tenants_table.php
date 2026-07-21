<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'appointments_allow_unpaid_reservation')) {
                $table->boolean('appointments_allow_unpaid_reservation')
                    ->default(true)
                    ->after('appointments_first_come_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'appointments_allow_unpaid_reservation')) {
                $table->dropColumn('appointments_allow_unpaid_reservation');
            }
        });
    }
};
