<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VerificationUsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'role_id'   => 3, // KEPALA_UPA
                'username'  => 'kepala_upa',
                'password'  => Hash::make('123456'),
                'full_name' => 'Kepala UPA',
                'is_active' => 1,
            ],
            [
                'role_id'   => 4, // KEPALA_DIVISI
                'username'  => 'kepala_divisi',
                'password'  => Hash::make('123456'),
                'full_name' => 'Kepala Divisi Teknis',
                'is_active' => 1,
            ],
        ];

        foreach ($users as $user) {
            $exists = DB::table('users')
                ->where('username', $user['username'])
                ->exists();

            if (!$exists) {
                DB::table('users')->insert(array_merge($user, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
