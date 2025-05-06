<?php
namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::create([
            'branch_name'   => 'Goran',
            'branch_prefix' => 'G',
            'address'       => '401/A South Goran, Khilgaon, Dhaka-1219',
            'phone_number'  => '01973033299',
        ]);

        Branch::create([
            'branch_name'   => 'Khilgaon',
            'branch_prefix' => 'K',
            'address'       => '822 Block#A, Ekota Street, Khilgaon, Dhaka-1219',
            'phone_number'  => '01973033699',
        ]);
    }
}
