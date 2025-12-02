<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->string('tax_name')->nullable()->after('amount');
            $table->decimal('tax_rate', 6,4)->nullable()->after('tax_name');
            $table->decimal('tax_amount', 10,2)->nullable()->after('tax_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->dropColumn('tax_name');
            $table->dropColumn('tax_rate');
            $table->dropColumn('tax_amount');
        });
    }
};
