<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('providers') && !Schema::hasColumn('providers', 'payment_currency_code')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->string('payment_currency_code', 3)->default('USD')->after('phone_number');
            });
        }

        if (Schema::hasTable('purchase_order_detail')) {
            Schema::table('purchase_order_detail', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_order_detail', 'input_currency_code')) {
                    $table->string('input_currency_code', 3)->nullable()->after('price');
                }

                if (!Schema::hasColumn('purchase_order_detail', 'input_exchange_rate')) {
                    $table->decimal('input_exchange_rate', 12, 6)->nullable()->after('input_currency_code');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('providers') && Schema::hasColumn('providers', 'payment_currency_code')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('payment_currency_code');
            });
        }

        if (Schema::hasTable('purchase_order_detail')) {
            Schema::table('purchase_order_detail', function (Blueprint $table) {
                if (Schema::hasColumn('purchase_order_detail', 'input_exchange_rate')) {
                    $table->dropColumn('input_exchange_rate');
                }

                if (Schema::hasColumn('purchase_order_detail', 'input_currency_code')) {
                    $table->dropColumn('input_currency_code');
                }
            });
        }
    }
};
