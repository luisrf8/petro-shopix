<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_methods', 'requires_proof_image')) {
                $table->boolean('requires_proof_image')->nullable()->after('has_reference');
            }
        });

        if (Schema::hasColumn('payment_methods', 'requires_proof_image') && Schema::hasColumn('payment_methods', 'has_reference')) {
            DB::table('payment_methods')
                ->whereNull('requires_proof_image')
                ->update([
                    'requires_proof_image' => DB::raw('CASE WHEN has_reference = 1 THEN 1 ELSE 0 END'),
                ]);
        }

        if (Schema::hasColumn('payment_methods', 'requires_proof_image')) {
            DB::table('payment_methods')
                ->whereNull('requires_proof_image')
                ->update(['requires_proof_image' => 1]);

            DB::statement('ALTER TABLE payment_methods MODIFY requires_proof_image TINYINT(1) NOT NULL DEFAULT 1');
        }
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            if (Schema::hasColumn('payment_methods', 'requires_proof_image')) {
                $table->dropColumn('requires_proof_image');
            }
        });
    }
};
