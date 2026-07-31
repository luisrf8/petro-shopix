<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'dni')) {
            return;
        }

        $dniCanBeNull = $this->usersDniIsNullable();
        $replacementValue = $dniCanBeNull ? null : '';

        DB::table('users')
            ->whereNotNull('google_id')
            ->where(function ($query) {
                $query->where('dni', 'like', 'SOC-GOOGLE-%')
                    ->orWhereColumn('dni', 'google_id');
            })
            ->update(['dni' => $replacementValue]);

        DB::table('users')
            ->whereNotNull('facebook_id')
            ->where(function ($query) {
                $query->where('dni', 'like', 'SOC-FACEBOOK-%')
                    ->orWhereColumn('dni', 'facebook_id');
            })
            ->update(['dni' => $replacementValue]);

        DB::table('users')
            ->whereNotNull('apple_id')
            ->where(function ($query) {
                $query->where('dni', 'like', 'SOC-APPLE-%')
                    ->orWhereColumn('dni', 'apple_id');
            })
            ->update(['dni' => $replacementValue]);
    }

    private function usersDniIsNullable(): bool
    {
        $database = DB::connection()->getDatabaseName();

        if (!is_string($database) || $database === '') {
            return false;
        }

        $column = DB::table('information_schema.COLUMNS')
            ->select('IS_NULLABLE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', 'users')
            ->where('COLUMN_NAME', 'dni')
            ->first();

        return strtoupper((string) ($column->IS_NULLABLE ?? 'NO')) === 'YES';
    }

    public function down(): void
    {
        // No reversible transformation: previous DNI values were placeholders or provider IDs.
    }
};
