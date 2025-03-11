<?php

namespace Database\Factories\Student;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Student\Student;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Academic\Shift;
use App\Models\Student\StudentActivation;
use App\Models\Reference;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        // Generate admission year (current year)
        $year = Carbon::now()->format('y'); // e.g., '24' for 2024

        // Select a random class or create one if none exist
        $class = ClassName::inRandomOrder()->first() ?? ClassName::factory()->create();

        // Determine academic group based on class numeral
        $academic_group = in_array($class->class_numeral, ['01', '02', '03', '04', '05', '06', '07', '08']) 
            ? 'General' 
            : $this->faker->randomElement(['Science', 'Commerce', 'Arts']);

        // Generate a unique roll number (1-99)
        $roll = str_pad($this->faker->unique()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT);

        // Generate student_unique_id (format: YYCCRR)
        $studentUniqueId = "{$year}{$class}{$roll}";

        return [
            'student_unique_id' => $studentUniqueId,
            'branch_id' => 1,
            'full_name' => $this->faker->name,
            'date_of_birth' => $this->faker->date('Y-m-d', now()->subYears(17)),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'class_id' => $class,
            'academic_group' => $academic_group,
            'shift_id' => Shift::inRandomOrder()->first()->id ?? Shift::factory(),
            'institution_roll' => $roll,
            'institution_id' => Institution::inRandomOrder()->first()->id ?? null,
            'religion' => $this->faker->optional()->randomElement(['Islam', 'Hinduism', 'Christianity', 'Buddhism']),
            'home_address' => $this->faker->address,
            'email' => $this->faker->optional()->unique()->safeEmail,
            'password' => bcrypt('password'),
            'reference_id' => Reference::inRandomOrder()->first()->id ?? null,
            'student_activation_id' => StudentActivation::factory(),
            'photo_url' => $this->faker->optional()->imageUrl(),
            'remarks' => $this->faker->optional()->sentence(),
            'deleted_by' => null,
        ];
    }
}
