<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'phone_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone_number', 25)->nullable()->after('email');
                $table->index('phone_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'phone_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['phone_number']);
                $table->dropColumn('phone_number');
            });
        }
    }
};
