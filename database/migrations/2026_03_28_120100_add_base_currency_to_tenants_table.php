<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'base_currency')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('base_currency', 3)->default('USD')->after('phone_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'base_currency')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('base_currency');
            });
        }
    }
};
