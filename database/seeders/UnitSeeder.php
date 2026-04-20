<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Persen', 'symbol' => '%'],
            ['name' => 'Miligram per Liter', 'symbol' => 'mg/L'],
            ['name' => 'Parts Per Million', 'symbol' => 'ppm'],
            ['name' => 'Colony Forming Unit per mL', 'symbol' => 'CFU/mL'],
            ['name' => 'Derajat Celcius', 'symbol' => '°C'],
            ['name' => 'Mililiter', 'symbol' => 'mL'],
            ['name' => 'Liter', 'symbol' => 'L'],
            ['name' => 'Gram', 'symbol' => 'g'],
            ['name' => 'Miligram', 'symbol' => 'mg'],
            ['name' => 'Kilogram', 'symbol' => 'kg'],
            ['name' => 'Tanpa Satuan', 'symbol' => '-'],
            ['name' => 'NTU', 'symbol' => 'NTU'],
            ['name' => 'pH', 'symbol' => 'pH'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['name' => $unit['name']], $unit);
        }
    }
}
