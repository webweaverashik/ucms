<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'accountant']);
        
        Role::firstOrCreate(['name' => 'teacher']);
        Role::firstOrCreate(['name' => 'student']);
        Role::firstOrCreate(['name' => 'guardian']);
    }
}
