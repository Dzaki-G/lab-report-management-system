<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AnalisSeeder extends Seeder
{
    public function run(): void
    {
        $analysts = [
            [
                'role_id'   => 5, // ANALIS
                'username'  => 'analis1',
                'password'  => Hash::make('123456'),
                'full_name' => 'Analis Satu',
                'is_active' => 1,
            ],
            [
                'role_id'   => 5,
                'username'  => 'analis2',
                'password'  => Hash::make('123456'),
                'full_name' => 'Analis Dua',
                'is_active' => 1,
            ],
        ];

        foreach ($analysts as $analyst) {
            $exists = DB::table('users')
                ->where('username', $analyst['username'])
                ->exists();

            if (!$exists) {
                DB::table('users')->insert(array_merge($analyst, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
}
