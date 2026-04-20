<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'role_id'   => 1, // Super Admin
                'username'  => 'superadmin',
                'password'  => Hash::make('superadmin123'),
                'full_name' => 'Super Administrator',
            ],
            [
                'role_id'   => 2, // Admin
                'username'  => 'admin',
                'password'  => Hash::make('123456'),
                'full_name' => 'Administrator',
            ],
            [
                'role_id'   => 3, // Kepala UPA
                'username'  => 'kepala_upa',
                'password'  => Hash::make('123456'),
                'full_name' => 'Kepala UPA',
            ],
            [
                'role_id'   => 4, // Kepala Divisi
                'username'  => 'kepala_divisi',
                'password'  => Hash::make('123456'),
                'full_name' => 'Kepala Divisi',
            ],
            [
                'role_id'   => 5, // Analis
                'username'  => 'analis1',
                'password'  => Hash::make('123456'),
                'full_name' => 'Analis 1',
            ],
            [
                'role_id'   => 5, // Analis
                'username'  => 'analis2',
                'password'  => Hash::make('123456'),
                'full_name' => 'Analis 2',
            ],
        ];

        foreach ($accounts as $account) {
            $exists = DB::table('users')
                ->where('username', $account['username'])
                ->exists();

            if (!$exists) {
                DB::table('users')->insert([
                    'role_id'    => $account['role_id'],
                    'username'   => $account['username'],
                    'password'   => $account['password'],
                    'full_name'  => $account['full_name'],
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
