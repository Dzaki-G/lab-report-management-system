<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Prevent duplicate Super Admin
        $exists = DB::table('users')
            ->where('username', 'superadmin')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('users')->insert([
            'role_id'    => 1, // Super Admin
            'username'   => 'superadmin',
            'password'   => Hash::make('superadmin123'),
            'full_name'  => 'Super Administrator',
            'is_active'  => 1,
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);
    }
}
