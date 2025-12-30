<?php
namespace Database\Seeders\Cost;

use App\Models\Cost\CostType;
use Illuminate\Database\Seeder;

class CostTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Rent',
            'Electricity',
            'Internet',
            'Teacher Salary',
            'Staff Salary',
            'Snacks',
            'Maintenance',
            'Stationery',
        ];

        foreach ($types as $type) {
            CostType::firstOrCreate(['name' => $type]);
        }
    }
}
