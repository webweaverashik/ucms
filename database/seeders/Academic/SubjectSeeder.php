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
            'Class IV'        => $this->getJuniorSubjects(),
            'Class V'         => $this->getJuniorSubjects(),
            'Class VI'        => $this->getMiddleSchoolSubjects(),
            'Class VII'       => $this->getMiddleSchoolSubjects(),
            'Class VIII'      => $this->getMiddleSchoolSubjects(),
            'Class IX'        => $this->getSSCSubjects(),
            'SSC (25-26)'     => $this->getSSCSubjects(),
            'SSC (24-25)'     => $this->getSSCSubjects(),
            'HSC (26-27) Sci' => $this->getHSCScienceSubjects(),
            'HSC (26-27) Com' => $this->getHSCCommerceSubjects(),
            'HSC (25-26) Sci' => $this->getHSCScienceSubjects(),
            'HSC (25-26) Com' => $this->getHSCCommerceSubjects(),
            'HSC (24-25) Sci' => $this->getHSCScienceSubjects(),
            'HSC (24-25) Com' => $this->getHSCCommerceSubjects(),
        ];

        foreach ($subjectsByClassName as $className => $subjects) {
            $class = ClassName::where('name', $className)->first();
            if (! $class) {
                $this->command->warn("Class '{$className}' not found");
                continue;
            }

            foreach ($subjects as $subject) {
                Subject::updateOrCreate(
                    [
                        'name'           => $subject['name'],
                        'class_id'       => $class->id,
                        'academic_group' => $subject['group'],
                    ],
                    [
                        'subject_type' => $subject['type'],
                    ]
                );
            }
            $this->command->info("Seeded: {$className}");
        }
    }

    private function getJuniorSubjects(): array
    {
        return [
            ['name' => 'Bangla', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'English', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Math', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'General Science', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Religion', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Bangladesh & Global Studies', 'group' => 'General', 'type' => 'compulsory'],
        ];
    }

    private function getMiddleSchoolSubjects(): array
    {
        return [
            ['name' => 'Bangla', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'English', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Math', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Science', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Bangladesh & Global Studies', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'ICT', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Religion', 'group' => 'General', 'type' => 'compulsory'],
        ];
    }

    private function getSSCSubjects(): array
    {
        return [
            // General
            ['name' => 'Bangla', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'English', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Math', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'ICT', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Religion', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Arts & Culture', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Physical Education', 'group' => 'General', 'type' => 'compulsory'],

            // Science - Compulsory
            ['name' => 'Physics', 'group' => 'Science', 'type' => 'compulsory'],
            ['name' => 'Chemistry', 'group' => 'Science', 'type' => 'compulsory'],
            ['name' => 'Bangladesh & Global Studies', 'group' => 'Science', 'type' => 'compulsory'],
            // Science - Optional
            ['name' => 'Biology', 'group' => 'Science', 'type' => 'optional'],
            ['name' => 'Higher Math', 'group' => 'Science', 'type' => 'optional'],

            // Commerce - Compulsory
            ['name' => 'Accounting', 'group' => 'Commerce', 'type' => 'compulsory'],
            ['name' => 'Business Entrepreneurship', 'group' => 'Commerce', 'type' => 'compulsory'],
            ['name' => 'Finance & Banking', 'group' => 'Commerce', 'type' => 'compulsory'],
            ['name' => 'General Science', 'group' => 'Commerce', 'type' => 'compulsory'],
            // Commerce - Optional
            ['name' => 'Agriculture Studies', 'group' => 'Commerce', 'type' => 'optional'],
            ['name' => 'Home Science', 'group' => 'Commerce', 'type' => 'optional'],
        ];
    }

    private function getHSCScienceSubjects(): array
    {
        return [
            ['name' => 'Bangla', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'English', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'ICT', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Physics', 'group' => 'Science', 'type' => 'compulsory'],
            ['name' => 'Chemistry', 'group' => 'Science', 'type' => 'compulsory'],
            ['name' => 'Biology', 'group' => 'Science', 'type' => 'optional'],
            ['name' => 'Higher Math', 'group' => 'Science', 'type' => 'optional'],
        ];
    }

    private function getHSCCommerceSubjects(): array
    {
        return [
            ['name' => 'Bangla', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'English', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'ICT', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'Accounting', 'group' => 'Commerce', 'type' => 'compulsory'],
            ['name' => 'Business Management', 'group' => 'Commerce', 'type' => 'compulsory'],
            ['name' => 'Finance & Banking', 'group' => 'Commerce', 'type' => 'optional'],
            ['name' => 'Production Marketing', 'group' => 'Commerce', 'type' => 'optional'],
            ['name' => 'Economics', 'group' => 'Commerce', 'type' => 'optional'],
            ['name' => 'Statistics', 'group' => 'Commerce', 'type' => 'optional'],
        ];
    }
}
