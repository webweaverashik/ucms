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
            'Class 04'               => $this->getJuniorSubjects(),
            'Class 05'               => $this->getJuniorSubjects(),
            'Class 06'               => $this->getMiddleSchoolSubjects(),
            'Class 07'               => $this->getMiddleSchoolSubjects(),
            'Class 08'               => $this->getMiddleSchoolSubjects(),
            'Class 09'               => $this->getSSCSubjects(),
            'SSC (26-27)'            => $this->getSSCSubjects(),
            'SSC (25-26)'            => $this->getSSCSubjects(),
            'HSC (26-27)'            => $this->getHSCSubjects(),
            'HSC (25-26)'            => $this->getHSCSubjects(),
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

            // Arts - Compulsory

            // Arts - Optional
        ];
    }

    private function getHSCSubjects(): array
    {
        return [
            // ======================
            // General (Common)
            // ======================
            ['name' => 'Bangla', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'English', 'group' => 'General', 'type' => 'compulsory'],
            ['name' => 'ICT', 'group' => 'General', 'type' => 'compulsory'],

            // ======================
            // Science Group
            // ======================
            ['name' => 'Physics', 'group' => 'Science', 'type' => 'compulsory'],
            ['name' => 'Chemistry', 'group' => 'Science', 'type' => 'compulsory'],
            ['name' => 'Biology', 'group' => 'Science', 'type' => 'optional'],
            ['name' => 'Higher Math', 'group' => 'Science', 'type' => 'optional'],

            // ======================
            // Commerce Group
            // ======================
            ['name' => 'Accounting', 'group' => 'Commerce', 'type' => 'compulsory'],
            ['name' => 'Business Organisation & Management', 'group' => 'Commerce', 'type' => 'compulsory'],
            ['name' => 'Finance, Banking & Insurance', 'group' => 'Commerce', 'type' => 'optional'],
            ['name' => 'Production Management & Marketing', 'group' => 'Commerce', 'type' => 'optional'],
            ['name' => 'Economics', 'group' => 'Commerce', 'type' => 'optional'],
            ['name' => 'Statistics', 'group' => 'Commerce', 'type' => 'optional'],

            // ======================
            // Arts Group
            // ======================

        ];
    }

}
