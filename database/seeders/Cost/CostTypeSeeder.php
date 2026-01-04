<?php
namespace Database\Seeders\Cost;

use App\Models\Cost\CostType;
use Illuminate\Database\Seeder;

class CostTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name'        => 'Rent',
                'description' => 'Monthly rent for office or branch premises',
            ],
            [
                'name'        => 'Electricity',
                'description' => 'Electricity bills and power consumption expenses',
            ],
            [
                'name'        => 'Internet',
                'description' => 'Internet and network service charges',
            ],
            [
                'name'        => 'Teacher Salary',
                'description' => 'Monthly salary payments for teachers',
            ],
            [
                'name'        => 'Staff Salary',
                'description' => 'Monthly salary payments for non-teaching staff',
            ],
            [
                'name'        => 'Snacks',
                'description' => 'Refreshments and snacks for students and staff',
            ],
            [
                'name'        => 'Maintenance',
                'description' => 'Repair, servicing, and maintenance-related costs',
            ],
            [
                'name'        => 'Stationery',
                'description' => 'Office stationery and educational supplies',
            ],
            [
                'name'        => 'Others',
                'description' => 'Miscellaneous costs with custom descriptions',
            ],
        ];

        foreach ($types as $type) {
            CostType::firstOrCreate(
                ['name' => $type['name']],
                ['description' => $type['description']]
            );
        }
    }
}
