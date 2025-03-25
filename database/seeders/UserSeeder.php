<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample Super Admin User
        User::create([
            'name' => 'Ashfaq Kayes',
            'email' => 'admin@ucms.com',
            'mobile_number' => '01700000000',
            'password' => Hash::make('admin123'),
            'branch_id' => null, // Super Admin does not belong to any branch
            'email_verified_at' => now(),
        ]);

        // Goran Branch Manager
        User::create([
            'name' => 'Ahamed Shakib',
            'email' => 'manager@goran.com',
            'mobile_number' => '01800000000',
            'password' => Hash::make('manager123'),
            'branch_id' => 1, // Assuming branch ID 1 exists
            'email_verified_at' => now(),
        ]);

        // Goran Branch Accountant
        User::create([
            'name' => 'Ramjan Shaikh',
            'email' => 'accountant@goran.com',
            'mobile_number' => '01900000000',
            'password' => Hash::make('accountant123'),
            'branch_id' => 1,
            'email_verified_at' => now(),
        ]);

        // Khilgaon Branch Manager
        User::create([
            'name' => 'Naeem',
            'email' => 'manager@khilgaon.com',
            'mobile_number' => '01800000000',
            'password' => Hash::make('manager123'),
            'branch_id' => 2,
            'email_verified_at' => now(),
        ]);

        // Khilgaon Branch Accountant
        User::create([
            'name' => 'Arafat Sunny',
            'email' => 'accountant@khilgaon.com',
            'mobile_number' => '01900000000',
            'password' => Hash::make('accountant123'),
            'branch_id' => 2,
            'email_verified_at' => now(),
        ]);
    }
}
