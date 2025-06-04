<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Step 1: Create Roles
        // $adminRole      = Role::firstOrCreate(['name' => 'admin']);
        // $managerRole    = Role::firstOrCreate(['name' => 'manager']);
        // $accountantRole = Role::firstOrCreate(['name' => 'accountant']);

        // Step 2: Create Users and Assign Roles

        // Super Admin
        $admin = User::create([
            'name'          => 'Ashfaq Kayes',
            'email'         => 'admin@ucms.com',
            'mobile_number' => '01700000000',
            'password'      => Hash::make('admin123'),
            'branch_id'     => 0,
        ]);
        $admin->assignRole('admin');

        // Goran Branch Manager
        $manager1 = User::create([
            'name'          => 'Ahamed Shakib',
            'email'         => 'manager@goran.com',
            'mobile_number' => '01800000000',
            'password'      => Hash::make('manager123'),
            'branch_id'     => 1,
        ]);
        $manager1->assignRole('manager');


        // Goran Branch Accountant
        $accountant1 = User::create([
            'name'          => 'Ramjan Shaikh',
            'email'         => 'accountant@goran.com',
            'mobile_number' => '01900000000',
            'password'      => Hash::make('accountant123'),
            'branch_id'     => 1,
        ]);
        $accountant1->assignRole('accountant');

        // Khilgaon Branch Manager
        $manager2 = User::create([
            'name'          => 'Naeem',
            'email'         => 'manager@khilgaon.com',
            'mobile_number' => '01800000000',
            'password'      => Hash::make('manager123'),
            'branch_id'     => 2,
        ]);
        $manager2->assignRole('manager');


        // Khilgaon Branch Accountant
        $accountant2 = User::create([
            'name'          => 'Arafat Sunny',
            'email'         => 'accountant@khilgaon.com',
            'mobile_number' => '01900000000',
            'password'      => Hash::make('accountant123'),
            'branch_id'     => 2,
        ]);
        $accountant2->assignRole('accountant');
    }
}
