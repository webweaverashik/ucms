<?php
namespace Database\Factories\Student;

use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Academic\Shift;
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
        // Generate admission year (current year)
        $year = Carbon::now()->format('y');

        // Select a random branch or create one if none exist
        $branch = Branch::inRandomOrder()->first() ?? Branch::factory()->create();

        // Select a random class or create one if none exist
        $class = ClassName::inRandomOrder()->first() ?? ClassName::factory()->create();

        // Determine academic group based on class numeral
        $academic_group = in_array($class->class_numeral, ['01', '02', '03', '04', '05', '06', '07', '08']) ? 'General' : $this->faker->randomElement(['Science', 'Commerce', 'Arts']);

        // Generate a unique roll number (1-99)
        $roll = str_pad($this->faker->unique()->numberBetween(1, 99), 2, '0', STR_PAD_LEFT);

        // Generate student_unique_id (format: BP-YYCCRR)
        $studentUniqueId = "{$branch->branch_prefix}-{$year}{$class->class_numeral}{$roll}";

        $banglaNames = ['আরিফ হোসেন', 'সুমাইয়া আক্তার', 'রাকিব হাসান', 'জান্নাতুল ফেরদৌস', 'ইমরান খান', 'তাসনিম রহমান', 'সাকিব আহমেদ', 'নাদিয়া সুলতানা', 'ফাহিম চৌধুরী', 'মাহিয়া মিম', 'নাসির উদ্দিন', 'রুমকি বেগম', 'শামীম সরকার', 'সাদিয়া ইসলাম', 'জুবায়ের ভূঁইয়া', 'আফিয়া শেখ', 'তানভীর মিয়া', 'আয়েশা খাতুন', 'আসিফ আলী', 'ফারজানা হক'];

        return [
            'student_unique_id'     => $studentUniqueId,
            'branch_id'             => $branch->id,
            'name'                  => $this->faker->name,
            // 'name_bn' => $this->faker->randomElement($banglaNames),
            'date_of_birth'         => $this->faker->date(),
            'gender'                => $this->faker->randomElement(['male', 'female']),
            'class_id'              => $class->id,
            'academic_group'        => $academic_group,
            'shift_id'              => Shift::inRandomOrder()->first()->id ?? Shift::factory()->create()->id,
            // 'institution_roll' => $roll,
            'institution_id'        => Institution::inRandomOrder()->first()->id ?? Institution::factory()->create()->id,
            'religion'              => 'Islam',
            'home_address'          => $this->faker->address,
            'email'                 => function () {
                if (rand(1, 100) <= 30) {
                    return $this->faker->unique()->safeEmail();
                } else {
                    return null;
                }
            },
            'password'              => Hash::make('password'),
            'reference_id'          => Reference::inRandomOrder()->first()->id ?? Reference::factory()->create()->id,
            'student_activation_id' => null, // Set after creation
            'photo_url'             => null,
            'remarks'               => null,
            'deleted_by'            => null,
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
