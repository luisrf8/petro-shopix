<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('product_variants') || Schema::hasColumn('product_variants', 'quantity_input_mode')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('quantity_input_mode', 20)
                ->default('integer')
                ->after('unit_type')
                ->comment('Modo de captura para cantidades: integer o decimal');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_variants') || !Schema::hasColumn('product_variants', 'quantity_input_mode')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('quantity_input_mode');
        });
    }
};