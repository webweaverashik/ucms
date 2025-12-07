<?php
namespace Database\Seeders\Student;

use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Branch;
use App\Models\Student\Reference;
use App\Models\Student\Student;
use App\Models\Student\StudentActivation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $TOTAL = 200;
        $year  = Carbon::now()->format('y');

        // --- PREPARE DATA POOLS ---

        // 1. Branches
        $branches = Branch::all();
        if ($branches->isEmpty()) {
            $branches = Branch::factory()->count(3)->create();
        }

        // 2. Classes
        $classes = ClassName::all();
        if ($classes->isEmpty()) {
            $classes = ClassName::factory()->count(6)->create();
        }

        // 3. Batches (Get all IDs to pick randomly later)
        $batchIds = Batch::pluck('id')->toArray();
        if (empty($batchIds)) {
            $batchIds = Batch::factory()->count(3)->create()->pluck('id')->toArray();
        }

        // 4. Institutions (Get all IDs)
        $institutionIds = Institution::pluck('id')->toArray();
        if (empty($institutionIds)) {
            $institutionIds = Institution::factory()->count(5)->create()->pluck('id')->toArray();
        }

        // 5. References (Get all IDs)
        $referenceIds = Reference::pluck('id')->toArray();
        if (empty($referenceIds)) {
            $referenceIds = Reference::factory()->count(5)->create()->pluck('id')->toArray();
        }

        // Loop variables
        $branches    = $branches->values();
        $classes     = $classes->values();
        $branchCount = $branches->count();
        $classCount  = $classes->count();

        $created     = 0;
        $branchIndex = 0;
        $classIndex  = 0;

        while ($created < $TOTAL) {
            // Round-robin selection for Branch and Class
            $branch = $branches[$branchIndex % $branchCount];
            $class  = $classes[$classIndex % $classCount];

            // Get current max roll for this branch+class
            $maxRoll = Student::where('branch_id', $branch->id)
                ->where('class_id', $class->id)
                ->selectRaw('COALESCE(MAX(CAST(RIGHT(student_unique_id, 2) AS UNSIGNED)), 0) AS max_roll')
                ->value('max_roll') ?? 0;

            // Generate Unique ID
            do {
                $maxRoll++;
                $roll            = str_pad($maxRoll, 2, '0', STR_PAD_LEFT);
                $studentUniqueId = "{$branch->branch_prefix}-{$year}{$class->class_numeral}{$roll}";
            } while (Student::where('student_unique_id', $studentUniqueId)->exists());

            // --- CREATE STUDENT ---
            $student = Student::create([
                'student_unique_id' => $studentUniqueId,
                'branch_id'         => $branch->id,
                'name'              => fake()->name(),
                'date_of_birth'     => fake()->date(),
                'gender'            => fake()->randomElement(['male', 'female']),
                'class_id'          => $class->id,
                'academic_group'    => in_array($class->class_numeral, ['01', '02', '03', '04', '05', '06', '07', '08'])
                    ? 'General'
                    : fake()->randomElement(['Science', 'Commerce', 'Arts']),

                // Randomly pick from the pre-fetched arrays
                'batch_id'          => fake()->randomElement($batchIds),
                'institution_id'    => fake()->randomElement($institutionIds),

                // 30% chance of being null, 70% chance of having a reference
                'reference_id'      => fake()->boolean(70) ? fake()->randomElement($referenceIds) : null,

                'religion'          => 'Islam',
                'home_address'      => fake()->address(),
                'email'             => fake()->boolean(30) ? fake()->unique()->safeEmail() : null,
                'password'          => Hash::make('password'),
            ]);

            // Create Activation
            $activation = StudentActivation::factory()->create([
                'student_id' => $student->id,
            ]);

            // Assign activation back to student
            $student->update([
                'student_activation_id' => $activation->id,
            ]);

            $created++;
            $branchIndex++;
            $classIndex++;
        }

        $this->command->info("Successfully created {$created} active students.");
    }
}
