<?php
namespace Database\Seeders\Academic;

use App\Models\Academic\Batch;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    public function run(): void
    {
        $batches    = ['Usha', 'Orun', 'Proloy', 'Dhumketu'];
        $branchIds = [1, 2]; // Assuming 1 and 2 are your branch IDs

        foreach ($branchIds as $branchId) {
            foreach ($batches as $batchName) {
                Batch::create([
                    'name'      => $batchName,
                    'branch_id' => $branchId,
                ]);
            }
        }
    }
}
