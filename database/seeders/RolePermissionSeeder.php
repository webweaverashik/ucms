<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define your roles
        $roles = ['admin', 'manager', 'accountant', 'student', 'teacher', 'guardian'];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name'       => $role,
                'guard_name' => 'web',
            ]);
        }

        // Define permissions grouped by module (for readability only)
        $permissions = [
            // 🎓 students
            'students.view',
            'students.create',
            'students.edit',
            'students.delete',
            'students.approve',
            'students.deactivate',
            'students.form.download',
            'students.promote',
            'students.transfer',

            // 👨‍👩‍👧 guardians
            'guardians.view',
            'guardians.create',
            'guardians.edit',
            'guardians.delete',

            // 👨‍👧‍👦 siblings
            'siblings.view',
            'siblings.edit',
            'siblings.delete',

            // 💸 invoices
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',

            // 💸 transactions
            'transactions.view',
            'transactions.create',
            'transactions.payslip.download',

            // 📦 sheets
            'sheets.view',
            'sheets.create',
            'sheets.edit',
            'sheets.delete',
            'sheets.distribute',

            // 🗓️ attendance
            'attendance.mark',
            'attendance.view',

            // 📚 subjects
            'subjects.manage',

            // 🏷️ classes
            'classes.manage',

            // 🕑 shifts
            'shifts.manage',

            // 🧑‍🏫 teachers
            'teachers.view',
            'teachers.create',
            'teachers.edit',
            'teachers.delete',
            'teachers.deactivate',
            'teachers.salary.manage',
            'teachers.class.track',

            // 🏫 institutions
            'institutions.manage',

            // 🏢 branches
            'branches.manage',

            // 📩 sms
            'sms.send',
            'sms.logs.view',
            'sms.templates.manage',

            // 🔐 users
            'users.manage',

            // 🛡️ roles
            'roles.manage',

            // 🔑 permissions
            'permissions.manage',

            // ⚙️ settings
            'settings.manage',

            // 📊 reports
            'reports.view',

            // 🖥️ dashboard
            'dashboard.access',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }


        // Assign all permissions to admin
        $admin = Role::where('name', 'admin')->first();
        $admin->syncPermissions(Permission::all());
    }
}
