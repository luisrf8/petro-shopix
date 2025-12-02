<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sales_detail_tax', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_detail_id');
            
            // históricos
            $table->string('tax_name');
            $table->decimal('tax_rate', 6,4);
            $table->decimal('tax_amount', 10, 2);
            $table->timestamps();

            $table->foreign('sales_order_detail_id')
                ->references('id')->on('sales_order_details')
                ->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_detail_tax');
    }
};
