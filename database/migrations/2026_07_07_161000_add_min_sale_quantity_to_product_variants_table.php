<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('product_variants') || Schema::hasColumn('product_variants', 'min_sale_quantity')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('min_sale_quantity', 10, 2)
                ->default(1)
                ->after('quantity_input_mode')
                ->comment('Cantidad minima de venta por variante');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_variants') || !Schema::hasColumn('product_variants', 'min_sale_quantity')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('min_sale_quantity');
        });
    }
};