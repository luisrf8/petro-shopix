<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('status')->constrained('payment_methods')->nullOnDelete();
            $table->decimal('paid_amount', 12, 2)->nullable()->after('payment_method_id');
            $table->string('payment_currency', 10)->nullable()->after('paid_amount');
            $table->string('payment_reference')->nullable()->after('payment_currency');
            $table->string('payment_status', 30)->default('pending')->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn(['paid_amount', 'payment_currency', 'payment_reference', 'payment_status']);
        });
    }
};