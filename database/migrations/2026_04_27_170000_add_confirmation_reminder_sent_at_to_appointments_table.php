<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'confirmation_reminder_sent_at')) {
                $table->timestamp('confirmation_reminder_sent_at')->nullable()->after('rescheduled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'confirmation_reminder_sent_at')) {
                $table->dropColumn('confirmation_reminder_sent_at');
            }
        });
    }
};
