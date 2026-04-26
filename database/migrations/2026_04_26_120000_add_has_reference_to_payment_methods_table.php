<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('has_reference')->default(true)->after('bank');
        });

        DB::table('payment_methods')
            ->whereIn(DB::raw('LOWER(name)'), ['efectivo', 'punto de venta', 'pago movil'])
            ->update(['has_reference' => false]);
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('has_reference');
        });
    }
};