<?php

namespace Database\Seeders;

use App\Models\EmployeeDesignation;
use Illuminate\Database\Seeder;

class EmployeeDesignationSeeder extends Seeder
{
    /**
     * Seed the example designations shown throughout the UI preview.
     */
    public function run(): void
    {
        $designations = [
            ['name' => 'Tax Consultant', 'is_active' => true],
            ['name' => 'Senior Associate', 'is_active' => true],
            ['name' => 'Article Assistant', 'is_active' => true],
            ['name' => 'Audit Executive', 'is_active' => true],
            ['name' => 'Office Manager', 'is_active' => false],
        ];

        foreach ($designations as $data) {
            EmployeeDesignation::query()->updateOrCreate(['name' => $data['name']], $data);
        }
    }
}
