<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'billing_logo')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('billing_logo')->nullable()->after('logo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'billing_logo')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('billing_logo');
            });
        }
    }
};