<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterSeeder extends Seeder
{
    public function run(): void
    {
        $parameters = [
            ['name' => 'SEM', 'category' => 'Mikroskopi', 'default_method' => 'SEM-EDS', 'default_unit' => '-'],
            ['name' => 'ICP', 'category' => 'Spektroskopi', 'default_method' => 'ICP-OES', 'default_unit' => 'ppm'],
            ['name' => 'pH', 'category' => 'Kimia', 'default_method' => 'Potensiometri', 'default_unit' => '-'],
            ['name' => 'Nitrat', 'category' => 'Kimia', 'default_method' => 'Spektrofotometri', 'default_unit' => 'mg/L'],
            ['name' => 'COD', 'category' => 'Kimia', 'default_method' => 'Titrimetri', 'default_unit' => 'mg/L'],
        ];

        foreach ($parameters as $param) {
            Parameter::updateOrCreate(
                ['name' => $param['name']],
                $param
            );
        }
    }
}
