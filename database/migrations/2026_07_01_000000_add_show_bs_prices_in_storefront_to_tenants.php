<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'show_bs_prices_in_storefront')) {
                $table->boolean('show_bs_prices_in_storefront')->default(false)->after('delivery_notifications_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'show_bs_prices_in_storefront')) {
                $table->dropColumn('show_bs_prices_in_storefront');
            }
        });
    }
};