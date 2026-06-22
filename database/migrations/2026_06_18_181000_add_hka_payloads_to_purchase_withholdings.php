<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('purchase_vat_retentions')) {
            Schema::table('purchase_vat_retentions', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_vat_retentions', 'request_payload')) {
                    $table->json('request_payload')->nullable()->after('notes');
                }

                if (!Schema::hasColumn('purchase_vat_retentions', 'response_payload')) {
                    $table->json('response_payload')->nullable()->after('request_payload');
                }
            });
        }

        if (Schema::hasTable('islr_withholdings')) {
            Schema::table('islr_withholdings', function (Blueprint $table) {
                if (!Schema::hasColumn('islr_withholdings', 'request_payload')) {
                    $table->json('request_payload')->nullable()->after('notes');
                }

                if (!Schema::hasColumn('islr_withholdings', 'response_payload')) {
                    $table->json('response_payload')->nullable()->after('request_payload');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_vat_retentions')) {
            Schema::table('purchase_vat_retentions', function (Blueprint $table) {
                foreach (['response_payload', 'request_payload'] as $column) {
                    if (Schema::hasColumn('purchase_vat_retentions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('islr_withholdings')) {
            Schema::table('islr_withholdings', function (Blueprint $table) {
                foreach (['response_payload', 'request_payload'] as $column) {
                    if (Schema::hasColumn('islr_withholdings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};