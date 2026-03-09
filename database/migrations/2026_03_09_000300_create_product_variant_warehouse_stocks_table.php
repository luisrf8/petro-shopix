<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variant_warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'warehouse_id', 'product_variant_id'], 'tenant_wh_variant_unique');
        });

        if (Schema::hasTable('products') && Schema::hasTable('product_variants') && Schema::hasTable('warehouses')) {
            $defaultWarehouses = DB::table('warehouses')->where('is_default', true)->get();

            foreach ($defaultWarehouses as $warehouse) {
                $variants = DB::table('product_variants')
                    ->join('products', 'products.id', '=', 'product_variants.product_id')
                    ->where('products.tenant_id', $warehouse->tenant_id)
                    ->select('product_variants.id', 'product_variants.stock')
                    ->get();

                foreach ($variants as $variant) {
                    DB::table('product_variant_warehouse_stocks')->insert([
                        'tenant_id' => $warehouse->tenant_id,
                        'warehouse_id' => $warehouse->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $variant->stock ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_warehouse_stocks');
    }
};
