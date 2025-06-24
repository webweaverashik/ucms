<?php
namespace Database\Seeders\Academic;

use App\Models\Academic\ClassName;
use App\Models\Academic\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjectsByClass = [
            // Class 04–05
            '04' => ['Bangla', 'English', 'Math', 'General Science', 'Religion', 'Bangladesh & Global Studies'],
            '05' => ['Bangla', 'English', 'Math', 'General Science', 'Religion', 'Bangladesh & Global Studies'],

            // Class 06–08
            '06' => ['Bangla 1st', 'Bangla 2nd', 'English 1st', 'English 2nd', 'Math', 'Science', 'Bangladesh & Global Studies', 'ICT', 'Religion'],
            '07' => ['Bangla 1st', 'Bangla 2nd', 'English 1st', 'English 2nd', 'Math', 'Science', 'Bangladesh & Global Studies', 'ICT', 'Religion'],
            '08' => ['Bangla 1st', 'Bangla 2nd', 'English 1st', 'English 2nd', 'Math', 'Science', 'Bangladesh & Global Studies', 'ICT', 'Religion'],

            // Class 09–10
            '09' => [
                // General
                ['name' => 'Bangla 1st', 'group' => 'General'],
                ['name' => 'Bangla 2nd', 'group' => 'General'],
                ['name' => 'English 1st', 'group' => 'General'],
                ['name' => 'English 2nd', 'group' => 'General'],
                ['name' => 'Math', 'group' => 'General'],
                ['name' => 'ICT', 'group' => 'General'],
                ['name' => 'Religion', 'group' => 'General'],

                // Science
                ['name' => 'Physics', 'group' => 'Science'],
                ['name' => 'Chemistry', 'group' => 'Science'],
                ['name' => 'Biology', 'group' => 'Science'],
                ['name' => 'Higher Math', 'group' => 'Science'],
                ['name' => 'Bangladesh & Global Studies', 'group' => 'Science'],

                // Commerce
                ['name' => 'Business Entrepreneurship', 'group' => 'Commerce'],
                ['name' => 'Management', 'group' => 'Commerce'],
                ['name' => 'Marketing', 'group' => 'Commerce'],
                ['name' => 'Finance & Banking', 'group' => 'Commerce'],
                ['name' => 'Accounting', 'group' => 'Commerce'],
                ['name' => 'Statistics', 'group' => 'Commerce'],
                ['name' => 'General Science', 'group' => 'Commerce'],

                // Arts
                // ['name' => 'Geography', 'group' => 'Arts'],
                // ['name' => 'Civics', 'group' => 'Arts'],
                // ['name' => 'Economics', 'group' => 'Arts'],
                // ['name' => 'History', 'group' => 'Arts'],
            ],
            '10' => 'same_as_09',

            // Class 11–12
            '11' => [
                // General
                ['name' => 'Bangla 1st', 'group' => 'General'],
                ['name' => 'Bangla 2nd', 'group' => 'General'],
                ['name' => 'English 1st', 'group' => 'General'],
                ['name' => 'English 2nd', 'group' => 'General'],
                ['name' => 'ICT', 'group' => 'General'],

                // Science
                ['name' => 'Physics 1st', 'group' => 'Science'],
                ['name' => 'Physics 2nd', 'group' => 'Science'],
                ['name' => 'Chemistry 1st', 'group' => 'Science'],
                ['name' => 'Chemistry 2nd', 'group' => 'Science'],
                ['name' => 'Biology 1st', 'group' => 'Science'],
                ['name' => 'Biology 2nd', 'group' => 'Science'],
                ['name' => 'Higher Math 1st', 'group' => 'Science'],
                ['name' => 'Higher Math 2nd', 'group' => 'Science'],

                // Commerce
                ['name' => 'Accounting 1st', 'group' => 'Commerce'],
                ['name' => 'Accounting 2nd', 'group' => 'Commerce'],
                ['name' => 'Management 1st', 'group' => 'Commerce'],
                ['name' => 'Management 2nd', 'group' => 'Commerce'],
                ['name' => 'Finance 1st', 'group' => 'Commerce'],
                ['name' => 'Finance 2nd', 'group' => 'Commerce'],
                ['name' => 'Marketing 1st', 'group' => 'Commerce'],
                ['name' => 'Marketing 2nd', 'group' => 'Commerce'],
            ],
            '12' => 'same_as_11',
        ];

        foreach ($subjectsByClass as $classNumeral => $subjects) {
            if ($subjects === 'same_as_09') {
                $subjects = $subjectsByClass['09'];
            } elseif ($subjects === 'same_as_11') {
                $subjects = $subjectsByClass['11'];
            }

            $class = ClassName::where('class_numeral', $classNumeral)->first();
            if (!$class) continue;

            foreach ($subjects as $subject) {
                if (is_string($subject)) {
                    Subject::firstOrCreate([
                        'name' => $subject,
                        'class_id' => $class->id,
                        'academic_group' => 'General',
                    ]);
                } else {
                    Subject::firstOrCreate([
                        'name' => $subject['name'],
                        'class_id' => $class->id,
                        'academic_group' => $subject['group'],
                    ]);
                }
            }
        }
    }
}

