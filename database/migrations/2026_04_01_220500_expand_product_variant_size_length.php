<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_variants') || !Schema::hasColumn('product_variants', 'size')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('size', 100)->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('product_variants') || !Schema::hasColumn('product_variants', 'size')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('size', 10)->change();
        });
    }
};