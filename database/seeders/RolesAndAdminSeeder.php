<?php
// database/seeders/RolesAndAdminSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Crear roles
        $superownerRole = Role::firstOrCreate(['name' => 'superowner']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $sedeAdminRole = Role::firstOrCreate(['name' => 'sede_admin']);
        $vendorRole = Role::firstOrCreate(['name' => 'vendor']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Crear el usuario administrador corporativo
        User::firstOrCreate(
            ['email' => 'superowner@example.com'],
            [
                'name' => 'Superowner',
                'password' => Hash::make('superowner'),
                'role_id' => $superownerRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin'),
                'role_id' => $adminRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'sede-admin@example.com'],
            [
                'name' => 'Admin de sede',
                'password' => Hash::make('sede-admin'),
                'role_id' => $sedeAdminRole->id,
            ]
        );

        // Crear un usuario vendedor de ejemplo
        User::firstOrCreate(
            ['email' => 'vendor@example.com'],
            [
                'name' => 'Vendor',
                'password' => Hash::make('vendor'),
                'role_id' => $vendorRole->id,
            ]
        );

        // Crear un usuario normal de ejemplo
        User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'User',
                'password' => Hash::make('user'),
                'role_id' => $userRole->id,
            ]
        );
    }
}
