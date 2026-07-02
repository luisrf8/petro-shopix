<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_retention_agent')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_retention_agent')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_retention_agent')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_retention_agent');
            });
        }
    }
};