<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tenant_plan_payments')) {
            return;
        }

        if (!Schema::hasColumn('tenant_plan_payments', 'expires_at')) {
            Schema::table('tenant_plan_payments', function (Blueprint $table) {
                $table->dateTime('expires_at')->nullable()->after('paid_at');
            });
        }

        if (!Schema::hasColumn('tenant_plan_payments', 'expires_at')) {
            return;
        }

        if (!Schema::hasTable('plans')) {
            return;
        }

        $planDurations = DB::table('plans')->pluck('duration_days', 'id');

        DB::table('tenant_plan_payments')
            ->where('status', 'paid')
            ->whereNull('expires_at')
            ->orderBy('id')
            ->chunkById(200, function ($payments) use ($planDurations) {
                foreach ($payments as $payment) {
                    $durationDays = max(0, (int) ($planDurations[$payment->plan_id] ?? 0));
                    $baseDate = $payment->paid_at ?: $payment->created_at ?: now();
                    $expiresAt = Carbon::parse($baseDate)->addDays($durationDays);

                    DB::table('tenant_plan_payments')
                        ->where('id', $payment->id)
                        ->update(['expires_at' => $expiresAt]);
                }
            });
    }

    public function down(): void
    {
        // No se elimina expires_at para evitar pérdida de datos históricos.
    }
};
