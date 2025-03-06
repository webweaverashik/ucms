<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::insert([
            [
                'branch_name' => 'Goran Branch',
                'address' => '123, Gulshan, Dhaka, Bangladesh',
                'phone_number' => '01700000001',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'branch_name' => 'Khilgaon Branch',
                'address' => '456, Agrabad, Chittagong, Bangladesh',
                'phone_number' => '01800000002',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
