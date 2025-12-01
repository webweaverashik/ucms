<?php
namespace Database\Seeders\Academic;

use App\Models\Academic\Batch;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $batches   = ['Usha', 'Orun', 'Proloy', 'Dhumketu'];
        $daysOff   = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $branchIds = [1, 2];

        foreach ($branchIds as $branchId) {
            foreach ($batches as $batchName) {
                Batch::create([
                    'name'      => $batchName,
                    'branch_id' => $branchId,
                    'day_off'   => $daysOff[array_rand($daysOff)], // random day
                ]);
            }
        }
    }
}
