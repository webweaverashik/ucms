<?php
namespace Database\Seeders;

use Database\Seeders\Academic\BatchSeeder;
use Database\Seeders\Academic\ClassNameSeeder;
use Database\Seeders\Academic\InstitutionSeeder;
use Database\Seeders\Academic\SubjectSeeder;
use Database\Seeders\Payment\PaymentInvoiceTypeSeeder;
use Database\Seeders\Sheet\SheetSeeder;
use Database\Seeders\SMS\SmsTemplatesTableSeeder;
use Illuminate\Database\Seeder;

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
            BatchSeeder::class,
            InstitutionSeeder::class,

            // TeacherSeeder::class,
            // ReferenceSeeder::class,
            // StudentSeeder::class,
            // GuardianSeeder::class,
            // MobileNumberSeeder::class,
            // SiblingSeeder::class,

            SubjectSeeder::class,

            // SubjectTakenSeeder::class,
            // PaymentSeeder::class,
            // PaymentInvoiceSeeder::class,
            // PaymentTransactionSeeder::class,
            PaymentInvoiceTypeSeeder::class,

            SheetSeeder::class,
            // SheetTopicSeeder::class,

            SmsTemplatesTableSeeder::class,
            // SmsCampaignsTableSeeder::class,
            // SmsLogsTableSeeder::class,
        ]);
    }
}
