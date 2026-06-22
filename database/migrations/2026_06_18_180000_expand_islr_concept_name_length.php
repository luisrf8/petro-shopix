<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('islr_withholding_concepts') || !Schema::hasColumn('islr_withholding_concepts', 'name')) {
            return;
        }

        Schema::table('islr_withholding_concepts', function (Blueprint $table) {
            $table->string('name', 255)->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('islr_withholding_concepts') || !Schema::hasColumn('islr_withholding_concepts', 'name')) {
            return;
        }

        Schema::table('islr_withholding_concepts', function (Blueprint $table) {
            $table->string('name', 160)->change();
        });
    }
};