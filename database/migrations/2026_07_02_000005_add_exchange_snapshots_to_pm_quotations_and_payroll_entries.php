<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('pm_quotations')) {
            Schema::table('pm_quotations', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_quotations', 'exchange_rate_to_bs')) {
                    $table->decimal('exchange_rate_to_bs', 16, 6)->nullable()->after('currency_code');
                }

                if (!Schema::hasColumn('pm_quotations', 'base_rate_to_bs')) {
                    $table->decimal('base_rate_to_bs', 16, 6)->nullable()->after('exchange_rate_to_bs');
                }

                if (!Schema::hasColumn('pm_quotations', 'usd_rate_to_bs')) {
                    $table->decimal('usd_rate_to_bs', 16, 6)->nullable()->after('base_rate_to_bs');
                }
            });

            $quotationRows = DB::table('pm_quotations')
                ->select('id', 'tenant_id', 'currency_code', 'created_at')
                ->get();

            foreach ($quotationRows as $row) {
                $currencyCode = strtoupper(trim((string) ($row->currency_code ?? 'USD')));
                if (in_array($currencyCode, ['VES', 'VED', 'VEF', 'BSD'], true)) {
                    $currencyCode = 'BS';
                }

                $baseCurrencyCode = strtoupper(trim((string) (DB::table('tenants')->where('id', (int) $row->tenant_id)->value('base_currency') ?: 'USD')));
                if (!in_array($baseCurrencyCode, ['USD', 'EUR'], true)) {
                    $baseCurrencyCode = 'USD';
                }

                $pivotDate = $row->created_at ? substr((string) $row->created_at, 0, 10) : null;

                $resolveRate = function (string $code) use ($row, $pivotDate): ?float {
                    if ($code === 'BS') {
                        return 1.0;
                    }

                    $table = $code === 'EUR' ? 'euro_rates' : 'dollar_rates';

                    $query = DB::table($table)
                        ->where('tenant_id', (int) $row->tenant_id);

                    if ($pivotDate) {
                        $query->whereDate('date', '<=', $pivotDate);
                    }

                    $rate = $query
                        ->orderByDesc('date')
                        ->orderByDesc('id')
                        ->value('rate');

                    if (!is_null($rate)) {
                        return (float) $rate;
                    }

                    return (float) (DB::table($table)
                        ->where('tenant_id', (int) $row->tenant_id)
                        ->orderByDesc('date')
                        ->orderByDesc('id')
                        ->value('rate') ?: 0);
                };

                DB::table('pm_quotations')
                    ->where('id', (int) $row->id)
                    ->update([
                        'exchange_rate_to_bs' => $resolveRate($currencyCode),
                        'base_rate_to_bs' => $resolveRate($baseCurrencyCode),
                        'usd_rate_to_bs' => $resolveRate('USD'),
                    ]);
            }
        }

        if (Schema::hasTable('pm_payroll_entries')) {
            Schema::table('pm_payroll_entries', function (Blueprint $table) {
                if (!Schema::hasColumn('pm_payroll_entries', 'exchange_rate_to_bs')) {
                    $table->decimal('exchange_rate_to_bs', 16, 6)->nullable()->after('currency_code');
                }

                if (!Schema::hasColumn('pm_payroll_entries', 'amount_bs')) {
                    $table->decimal('amount_bs', 14, 4)->nullable()->after('total_to_pay');
                }

                if (!Schema::hasColumn('pm_payroll_entries', 'total_to_pay_bs')) {
                    $table->decimal('total_to_pay_bs', 14, 4)->nullable()->after('amount_bs');
                }
            });

            $payrollRows = DB::table('pm_payroll_entries')
                ->select('id', 'tenant_id', 'currency_code', 'paid_at', 'amount', 'total_to_pay')
                ->get();

            foreach ($payrollRows as $row) {
                $currencyCode = strtoupper(trim((string) ($row->currency_code ?? 'USD')));
                if (in_array($currencyCode, ['VES', 'VED', 'VEF', 'BSD'], true)) {
                    $currencyCode = 'BS';
                }

                $resolveRate = function (string $code) use ($row): ?float {
                    if ($code === 'BS') {
                        return 1.0;
                    }

                    $table = $code === 'EUR' ? 'euro_rates' : 'dollar_rates';

                    $query = DB::table($table)
                        ->where('tenant_id', (int) $row->tenant_id);

                    if (!empty($row->paid_at)) {
                        $query->whereDate('date', '<=', (string) $row->paid_at);
                    }

                    $rate = $query
                        ->orderByDesc('date')
                        ->orderByDesc('id')
                        ->value('rate');

                    if (!is_null($rate)) {
                        return (float) $rate;
                    }

                    return (float) (DB::table($table)
                        ->where('tenant_id', (int) $row->tenant_id)
                        ->orderByDesc('date')
                        ->orderByDesc('id')
                        ->value('rate') ?: 0);
                };

                $rateToBs = $resolveRate($currencyCode);
                $amount = (float) ($row->amount ?? 0);
                $totalToPay = (float) ($row->total_to_pay ?? $row->amount ?? 0);

                $amountBs = null;
                $totalToPayBs = null;

                if (!is_null($rateToBs) && $rateToBs > 0) {
                    if ($currencyCode === 'BS') {
                        $amountBs = $amount;
                        $totalToPayBs = $totalToPay;
                    } else {
                        $amountBs = $amount * $rateToBs;
                        $totalToPayBs = $totalToPay * $rateToBs;
                    }
                }

                DB::table('pm_payroll_entries')
                    ->where('id', (int) $row->id)
                    ->update([
                        'exchange_rate_to_bs' => $rateToBs,
                        'amount_bs' => is_null($amountBs) ? null : round($amountBs, 4),
                        'total_to_pay_bs' => is_null($totalToPayBs) ? null : round($totalToPayBs, 4),
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pm_payroll_entries')) {
            Schema::table('pm_payroll_entries', function (Blueprint $table) {
                $drops = [];

                if (Schema::hasColumn('pm_payroll_entries', 'exchange_rate_to_bs')) {
                    $drops[] = 'exchange_rate_to_bs';
                }

                if (Schema::hasColumn('pm_payroll_entries', 'amount_bs')) {
                    $drops[] = 'amount_bs';
                }

                if (Schema::hasColumn('pm_payroll_entries', 'total_to_pay_bs')) {
                    $drops[] = 'total_to_pay_bs';
                }

                if (!empty($drops)) {
                    $table->dropColumn($drops);
                }
            });
        }

        if (Schema::hasTable('pm_quotations')) {
            Schema::table('pm_quotations', function (Blueprint $table) {
                $drops = [];

                if (Schema::hasColumn('pm_quotations', 'exchange_rate_to_bs')) {
                    $drops[] = 'exchange_rate_to_bs';
                }

                if (Schema::hasColumn('pm_quotations', 'base_rate_to_bs')) {
                    $drops[] = 'base_rate_to_bs';
                }

                if (Schema::hasColumn('pm_quotations', 'usd_rate_to_bs')) {
                    $drops[] = 'usd_rate_to_bs';
                }

                if (!empty($drops)) {
                    $table->dropColumn($drops);
                }
            });
        }
    }
};
