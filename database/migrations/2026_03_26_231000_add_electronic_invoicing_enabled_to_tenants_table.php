<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'electronic_invoicing_enabled')) {
                $table->boolean('electronic_invoicing_enabled')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'electronic_invoicing_enabled')) {
                $table->dropColumn('electronic_invoicing_enabled');
            }
        });
    }
};
