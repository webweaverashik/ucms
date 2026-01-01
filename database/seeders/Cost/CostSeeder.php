<?php
namespace Database\Seeders\Cost;

use App\Models\Branch;
use App\Models\Cost\Cost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CostSeeder extends Seeder
{
    public function run(): void
    {
        // Load branches with their users
        $branches = Branch::with('users')->get();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. CostSeeder skipped.');
            return;
        }

        // Last 30 days
        $dates = collect(range(0, 29))
            ->map(fn ($i) => Carbon::now()->subDays($i)->toDateString());

        foreach ($branches as $branch) {

            // ✅ Users from the SAME branch
            $branchUsers = $branch->users;

            if ($branchUsers->isEmpty()) {
                $this->command->warn(
                    "Skipping branch {$branch->branch_name} (no users found)"
                );
                continue;
            }

            foreach ($dates as $date) {

                // Prevent duplicate cost per branch per date
                $exists = Cost::where('branch_id', $branch->id)
                    ->whereDate('cost_date', $date)
                    ->exists();

                if ($exists) {
                    continue;
                }

                Cost::create([
                    'branch_id'   => $branch->id,
                    'cost_date'   => $date,
                    // ✅ Random user but SAME branch
                    'created_by'  => $branchUsers->random()->id,
                ]);
            }
        }

        $this->command->info(
            'CostSeeder executed successfully with branch-wise random users.'
        );
    }
}
