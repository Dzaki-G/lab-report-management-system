<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Prevent duplicate Admin
        $exists = DB::table('users')
            ->where('username', 'admin')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('users')->insert([
            'role_id'    => 2, // Admin
            'username'   => 'admin',
            'password'   => Hash::make('123456'),
            'full_name'  => 'Administrator',
            'is_active'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
