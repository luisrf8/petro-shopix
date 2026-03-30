<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_orders', 'entry_mode')) {
                    $table->string('entry_mode', 20)->default('purchase')->after('warehouse_id');
                }

                if (!Schema::hasColumn('purchase_orders', 'production_cost_total')) {
                    $table->decimal('production_cost_total', 14, 4)->nullable()->after('entry_mode');
                }

                if (!Schema::hasColumn('purchase_orders', 'production_notes')) {
                    $table->string('production_notes')->nullable()->after('production_cost_total');
                }
            });
        }

        if (!Schema::hasTable('purchase_order_consumptions')) {
            Schema::create('purchase_order_consumptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->foreignId('produced_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
                $table->foreignId('consumed_variant_id')->constrained('product_variants')->cascadeOnDelete();
                $table->decimal('quantity', 14, 4);
                $table->decimal('unit_cost', 14, 4);
                $table->decimal('amount', 14, 4);
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_consumptions');

        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_orders', 'production_notes')) {
                    $table->dropColumn('production_notes');
                }

                if (Schema::hasColumn('purchase_orders', 'production_cost_total')) {
                    $table->dropColumn('production_cost_total');
                }

                if (Schema::hasColumn('purchase_orders', 'entry_mode')) {
                    $table->dropColumn('entry_mode');
                }
            });
        }
    }
};
