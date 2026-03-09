<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('material_packages', function (Blueprint $table) {
            $table->decimal('package_price', 10, 2)->nullable()->after('discount_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('material_packages', function (Blueprint $table) {
            $table->dropColumn('package_price');
        });
    }
};
