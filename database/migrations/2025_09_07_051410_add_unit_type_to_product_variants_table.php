<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('product_variants') || Schema::hasColumn('product_variants', 'unit_type')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('unit_type')->default('unidad')->after('size')
                  ->comment('Tipo de unidad: unidad, gramo, litro, paquete, etc.');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_variants') || !Schema::hasColumn('product_variants', 'unit_type')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('unit_type');
        });
    }
};
