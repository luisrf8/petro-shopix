<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_service_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_service_id')->constrained('appointment_services')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['appointment_service_id', 'user_id'], 'appointment_service_user_unique');
            $table->index(['user_id', 'appointment_service_id'], 'appointment_service_user_lookup_idx');
        });

        // Backfill existing one-to-one assignments as pivot rows.
        $legacyAssignments = DB::table('appointment_services')
            ->whereNotNull('user_id')
            ->get(['id', 'user_id']);

        $now = now();
        $rows = [];
        foreach ($legacyAssignments as $assignment) {
            $serviceId = (int) ($assignment->id ?? 0);
            $userId = (int) ($assignment->user_id ?? 0);
            if ($serviceId <= 0 || $userId <= 0) {
                continue;
            }

            $rows[] = [
                'appointment_service_id' => $serviceId,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            DB::table('appointment_service_user')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_service_user');
    }
};
