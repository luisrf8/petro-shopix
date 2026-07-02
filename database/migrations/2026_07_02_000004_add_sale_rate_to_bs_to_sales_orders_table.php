<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'sale_rate_to_bs')) {
                $table->decimal('sale_rate_to_bs', 14, 6)->nullable()->after('sale_currency_code');
            }
        });

        DB::table('sales_orders')
            ->select(['id', 'sale_currency_code', 'change_rate_to_bs', 'sale_rate_to_bs'])
            ->orderBy('id')
            ->chunkById(200, function ($orders): void {
                foreach ($orders as $order) {
                    if (!is_null($order->sale_rate_to_bs) && (float) $order->sale_rate_to_bs > 0) {
                        continue;
                    }

                    $currency = strtoupper(trim((string) ($order->sale_currency_code ?? 'USD')));
                    if (in_array($currency, ['BS', 'VES', 'VED', 'VEF', 'BSD'], true)) {
                        DB::table('sales_orders')->where('id', (int) $order->id)->update(['sale_rate_to_bs' => 1]);
                        continue;
                    }

                    $changeRate = (float) ($order->change_rate_to_bs ?? 0);
                    if ($changeRate > 0) {
                        DB::table('sales_orders')->where('id', (int) $order->id)->update(['sale_rate_to_bs' => round($changeRate, 6)]);
                        continue;
                    }

                    $payment = DB::table('payments')
                        ->where('sales_order_id', (int) $order->id)
                        ->whereRaw("UPPER(TRIM(COALESCE(currency, ''))) IN ('BS', 'VES', 'VED', 'VEF', 'BSD')")
                        ->where('amount_original', '>', 0)
                        ->where('amount_base', '>', 0)
                        ->orderByDesc('id')
                        ->first(['amount_original', 'amount_base']);

                    if ($payment) {
                        $rate = (float) $payment->amount_original / (float) $payment->amount_base;
                        if ($rate > 0) {
                            DB::table('sales_orders')->where('id', (int) $order->id)->update(['sale_rate_to_bs' => round($rate, 6)]);
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'sale_rate_to_bs')) {
                $table->dropColumn('sale_rate_to_bs');
            }
        });
    }
};
