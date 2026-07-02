<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('store_expenses')) {
            return;
        }

        Schema::table('store_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('store_expenses', 'currency_code')) {
                $table->string('currency_code', 3)->default('USD')->after('amount');
            }

            if (!Schema::hasColumn('store_expenses', 'amount_original')) {
                $table->decimal('amount_original', 12, 4)->default(0)->after('currency_code');
            }

            if (!Schema::hasColumn('store_expenses', 'exchange_rate_to_bs')) {
                $table->decimal('exchange_rate_to_bs', 12, 4)->default(1)->after('amount_original');
            }

            if (!Schema::hasColumn('store_expenses', 'amount_bs')) {
                $table->decimal('amount_bs', 12, 2)->default(0)->after('exchange_rate_to_bs');
            }
        });

        DB::table('store_expenses')->update([
            'currency_code' => 'USD',
            'amount_original' => DB::raw('amount'),
            'exchange_rate_to_bs' => 1,
            'amount_bs' => DB::raw('amount'),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('store_expenses')) {
            return;
        }

        Schema::table('store_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('store_expenses', 'amount_bs')) {
                $table->dropColumn('amount_bs');
            }

            if (Schema::hasColumn('store_expenses', 'exchange_rate_to_bs')) {
                $table->dropColumn('exchange_rate_to_bs');
            }

            if (Schema::hasColumn('store_expenses', 'amount_original')) {
                $table->dropColumn('amount_original');
            }

            if (Schema::hasColumn('store_expenses', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};