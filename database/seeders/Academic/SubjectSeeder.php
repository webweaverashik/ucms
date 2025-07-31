<?php
namespace Database\Seeders\Academic;

use App\Models\Academic\ClassName;
use App\Models\Academic\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjectsByClassName = [
            // Class IV–V
            'Class IV'        => ['Bangla', 'English', 'Math', 'General Science', 'Religion', 'Bangladesh & Global Studies'],
            'Class V'         => ['Bangla', 'English', 'Math', 'General Science', 'Religion', 'Bangladesh & Global Studies'],

            // Class VI–VIII
            'Class VI'        => ['Bangla 1st', 'Bangla 2nd', 'English 1st', 'English 2nd', 'Math', 'Science', 'Bangladesh & Global Studies', 'ICT', 'Religion'],
            'Class VII'       => ['Bangla 1st', 'Bangla 2nd', 'English 1st', 'English 2nd', 'Math', 'Science', 'Bangladesh & Global Studies', 'ICT', 'Religion'],
            'Class VIII'      => ['Bangla 1st', 'Bangla 2nd', 'English 1st', 'English 2nd', 'Math', 'Science', 'Bangladesh & Global Studies', 'ICT', 'Religion'],

            // Class IX–X (Same subjects for all SSC batches)
            'Class IX'        => 'same_as_ssc',
            'SSC (25-26)'     => 'same_as_ssc',
            'SSC (24-25)'     => 'same_as_ssc',

            // HSC 11 (Science)
            'HSC (25-26) Sci' => 'same_as_hsc_sci',
            'HSC (24-25) Sci' => 'same_as_hsc_sci',

            // HSC 11 (Commerce)
            'HSC (25-26) Com' => 'same_as_hsc_com',
            'HSC (24-25) Com' => 'same_as_hsc_com',
        ];

        $sscSubjects = [
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
        ];

        $hscScienceSubjects = [
            ['name' => 'Bangla 1st', 'group' => 'General'],
            ['name' => 'Bangla 2nd', 'group' => 'General'],
            ['name' => 'English 1st', 'group' => 'General'],
            ['name' => 'English 2nd', 'group' => 'General'],
            ['name' => 'ICT', 'group' => 'General'],

            ['name' => 'Physics 1st', 'group' => 'Science'],
            ['name' => 'Physics 2nd', 'group' => 'Science'],
            ['name' => 'Chemistry 1st', 'group' => 'Science'],
            ['name' => 'Chemistry 2nd', 'group' => 'Science'],
            ['name' => 'Biology 1st', 'group' => 'Science'],
            ['name' => 'Biology 2nd', 'group' => 'Science'],
            ['name' => 'Higher Math 1st', 'group' => 'Science'],
            ['name' => 'Higher Math 2nd', 'group' => 'Science'],
        ];

        $hscCommerceSubjects = [
            ['name' => 'Bangla 1st', 'group' => 'General'],
            ['name' => 'Bangla 2nd', 'group' => 'General'],
            ['name' => 'English 1st', 'group' => 'General'],
            ['name' => 'English 2nd', 'group' => 'General'],
            ['name' => 'ICT', 'group' => 'General'],

            ['name' => 'Accounting 1st', 'group' => 'Commerce'],
            ['name' => 'Accounting 2nd', 'group' => 'Commerce'],
            ['name' => 'Management 1st', 'group' => 'Commerce'],
            ['name' => 'Management 2nd', 'group' => 'Commerce'],
            ['name' => 'Finance 1st', 'group' => 'Commerce'],
            ['name' => 'Finance 2nd', 'group' => 'Commerce'],
            ['name' => 'Marketing 1st', 'group' => 'Commerce'],
            ['name' => 'Marketing 2nd', 'group' => 'Commerce'],
        ];

        foreach ($subjectsByClassName as $className => $subjects) {
            $class = ClassName::where('name', $className)->first();
            if (! $class) {
                continue;
            }

            if ($subjects === 'same_as_ssc') {
                $subjects = $sscSubjects;
            } elseif ($subjects === 'same_as_hsc_sci') {
                $subjects = $hscScienceSubjects;
            } elseif ($subjects === 'same_as_hsc_com') {
                $subjects = $hscCommerceSubjects;
            }

            foreach ($subjects as $subject) {
                if (is_string($subject)) {
                    Subject::firstOrCreate([
                        'name'           => $subject,
                        'class_id'       => $class->id,
                        'academic_group' => 'General',
                    ]);
                } else {
                    Subject::firstOrCreate([
                        'name'           => $subject['name'],
                        'class_id'       => $class->id,
                        'academic_group' => $subject['group'],
                    ]);
                }
            }
        }
    }
}
