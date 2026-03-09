<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
public function up(): void
{
    Schema::create('material_package_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('material_package_id')->constrained('material_packages')->cascadeOnDelete();
        $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
        $table->decimal('quantity', 10, 2);
        $table->timestamps();

        // Pass a custom name (e.g., 'pkg_items_unique') as the second argument
        $table->unique(['material_package_id', 'product_variant_id'], 'mp_package_variant_unique');
    });
}

    public function down(): void
    {
        Schema::dropIfExists('material_package_items');
    }
};
