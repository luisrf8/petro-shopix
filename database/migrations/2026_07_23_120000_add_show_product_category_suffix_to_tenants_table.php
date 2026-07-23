<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'show_product_category_suffix')) {
                $table->boolean('show_product_category_suffix')
                    ->default(false)
                    ->after('show_bs_prices_in_storefront');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'show_product_category_suffix')) {
                $table->dropColumn('show_product_category_suffix');
            }
        });
    }
};
