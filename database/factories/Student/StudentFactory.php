<?php
namespace Database\Factories\Student;

use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Branch;
use App\Models\Student\Reference;
use App\Models\Student\Student;
use App\Models\Student\StudentActivation;
use Carbon\Carbon;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        // 2-digit year for admission id
        $year = Carbon::now()->format('y');

        // Pick (or create) branch & class
        $branch = Branch::inRandomOrder()->first() ?? Branch::factory()->create();
        $class  = ClassName::inRandomOrder()->first() ?? ClassName::factory()->create();

        // Determine academic group
        $academic_group = in_array($class->class_numeral, ['01', '02', '03', '04', '05', '06', '07', '08'])
            ? 'General'
            : $this->faker->randomElement(['Science', 'Commerce', 'Arts']);

        // --------- Sequential roll per branch+class ----------
        // static counters persist across factory calls during this process
        static $counters = [];

        $key = "{$branch->id}_{$class->id}";

        if (! isset($counters[$key])) {
            // Initialize from DB: find latest student for this branch+class and parse the roll part
            $lastStudent = Student::where('branch_id', $branch->id)
                ->where('class_id', $class->id)
                ->latest('id')
                ->first();

            if ($lastStudent && ! empty($lastStudent->student_unique_id)) {
                // student_unique_id format BP-YYCCRR -> take last 2 chars as roll
                $lastRoll = intval(substr($lastStudent->student_unique_id, -2));
            } else {
                $lastRoll = 0;
            }

            $counters[$key] = $lastRoll;
        }

        // increment for this new student
        $counters[$key]++;

        // keep RR always two digits
        $roll = str_pad($counters[$key], 2, '0', STR_PAD_LEFT);

        // Build student_unique_id: BP-YYCCRR
        $studentUniqueId = "{$branch->branch_prefix}-{$year}{$class->class_numeral}{$roll}";
        // -----------------------------------------------------

        return [
            'student_unique_id' => $studentUniqueId,
            'branch_id'         => $branch->id,
            'name'              => $this->faker->name,
            'date_of_birth'     => $this->faker->date(),
            'gender'            => $this->faker->randomElement(['male', 'female']),
            'class_id'          => $class->id,
            'academic_group'    => $academic_group,
            'batch_id'          => Batch::inRandomOrder()->first()->id ?? Batch::factory()->create()->id,
            'institution_id'    => Institution::inRandomOrder()->first()->id ?? Institution::factory()->create()->id,
            'religion'          => 'Islam',
            'home_address'      => $this->faker->address,
            'email'             => rand(1, 100) <= 30 ? $this->faker->unique()->safeEmail() : null,
            'password'          => Hash::make('password'),
            'reference_id'      => Reference::inRandomOrder()->first()->id ?? Reference::factory()->create()->id,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Student $student) {
            $activation = StudentActivation::factory()->create([
                'student_id' => $student->id,
            ]);
            $student->update(['student_activation_id' => $activation->id]);
        });
    }
}
