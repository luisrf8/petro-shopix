<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('product_variants') && Schema::hasColumn('product_variants', 'stock')) {
            DB::statement('ALTER TABLE product_variants MODIFY stock DECIMAL(12,2) NOT NULL DEFAULT 0');
        }

        if (Schema::hasTable('sales_order_details') && Schema::hasColumn('sales_order_details', 'quantity')) {
            DB::statement('ALTER TABLE sales_order_details MODIFY quantity DECIMAL(12,2) NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_variants') && Schema::hasColumn('product_variants', 'stock')) {
            DB::statement('UPDATE product_variants SET stock = ROUND(stock, 0)');
            DB::statement('ALTER TABLE product_variants MODIFY stock INT NOT NULL DEFAULT 0');
        }

        if (Schema::hasTable('sales_order_details') && Schema::hasColumn('sales_order_details', 'quantity')) {
            DB::statement('UPDATE sales_order_details SET quantity = ROUND(quantity, 0)');
            DB::statement('ALTER TABLE sales_order_details MODIFY quantity INT NOT NULL');
        }
    }
};