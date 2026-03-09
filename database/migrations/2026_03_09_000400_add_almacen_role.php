<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $exists = DB::table('roles')->whereRaw('LOWER(name) = ?', ['almacen'])->exists();
        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'almacen',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->whereRaw('LOWER(name) = ?', ['almacen'])->delete();
    }
};
