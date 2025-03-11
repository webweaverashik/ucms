<?php

namespace Database\Factories\Student;

use Carbon\Carbon;
use Faker\Generator as Faker;
use App\Models\Academic\Shift;
use App\Models\Student\Student;
use App\Models\Student\Reference;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Student\StudentActivation;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        // Generate admission year (current year)
        $year = Carbon::now()->format('y');

        // Select a random class or create one if none exist
        $class = ClassName::inRandomOrder()->first() ?? ClassName::factory()->create();

        // Determine academic group based on class numeral
        $academic_group = in_array($class->class_numeral, ['01', '02', '03', '04', '05', '06', '07', '08'])
            ? 'General'
            : $this->faker->randomElement(['Science', 'Commerce', 'Arts']);

        // Generate a unique roll number (1-999)
        $roll = str_pad($this->faker->unique()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT);
        // $roll = $this->faker->unique()->numberBetween(1, 999);

        // Generate student_unique_id (format: YYCCRR)
        $studentUniqueId = "{$year}{$class->class_numeral}{$roll}";

        // Handle optional email generation to prevent null errors
        $email = null; // Initialize to null
        if ($this->faker->boolean(80)) { // 80% chance of generating an email
            $email = $this->faker->unique()->safeEmail();
        }

        return [
            'student_unique_id' => $studentUniqueId,
            'branch_id' => 1,
            'full_name' => $this->faker->name,
            // 'date_of_birth' => $this->faker->date('Y-m-d', now()->subYears(17)),
            'date_of_birth' => $this->faker->date(),
            // 'gender' => $this->faker->randomElement(['male', 'female']),
            'gender' => 'male',
            'class_id' => $class->id,
            'academic_group' => $academic_group,
            // 'shift_id' => Shift::inRandomOrder()->first()->id ?? Shift::factory()->create()->id,
            'shift_id' => 1,
            // 'institution_roll' => $roll,
            'institution_roll' => null,
            // 'institution_id' => Institution::inRandomOrder()->first()->id ?? Institution::factory()->create()->id,
            'institution_id' => 2,
            'religion' => 'Islam',
            // 'home_address' => $this->faker->address,
            'home_address' => null,
            // 'email' => $email,
            'email' => null,
            'password' => bcrypt('password'),
            // 'reference_id' => Reference::inRandomOrder()->first()->id ?? Reference::factory()->create()->id,
            'reference_id' => 1,
            'student_activation_id' => null,
            'photo_url' => null,
            'remarks' => null,
            'deleted_by' => null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Student $student) {
            $activation = StudentActivation::factory()->create([
                'student_id' => $student->id, // ✅ Assign activation to this student
            ]);

            $student->update(['student_activation_id' => $activation->id]); // ✅ Update student record
        });
    }
}