<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\Sheet\SheetSeeder;
use Database\Seeders\Academic\ShiftSeeder;
use Database\Seeders\Payment\PaymentSeeder;
use Database\Seeders\Student\SiblingSeeder;
use Database\Seeders\Student\StudentSeeder;
use Database\Seeders\Teacher\TeacherSeeder;
use Database\Seeders\Academic\SubjectSeeder;
use Database\Seeders\Sheet\SheetTopicSeeder;
use Database\Seeders\Student\GuardianSeeder;
use Database\Seeders\Student\ReferenceSeeder;
use Database\Seeders\Academic\ClassNameSeeder;
use Database\Seeders\Academic\InstitutionSeeder;
use Database\Seeders\Student\MobileNumberSeeder;
use Database\Seeders\Academic\SubjectTakenSeeder;
use Database\Seeders\Payment\PaymentInvoiceSeeder;
use Database\Seeders\Student\StudentActivationSeeder;
use Database\Seeders\Payment\PaymentTransactionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            ClassNameSeeder::class,
            ShiftSeeder::class,
            InstitutionSeeder::class,
            // TeacherSeeder::class,
            // ReferenceSeeder::class,
            // StudentSeeder::class,
            // GuardianSeeder::class,
            // MobileNumberSeeder::class,
            // SiblingSeeder::class,
            // StudentActivationSeeder::class, // will be called by StudentFactory
            SubjectSeeder::class,
            // SubjectTakenSeeder::class,
            // PaymentSeeder::class,
            // PaymentInvoiceSeeder::class,
            // PaymentTransactionSeeder::class,
            SheetSeeder::class,
            // SheetTopicSeeder::class,
        ]);
    }
}
