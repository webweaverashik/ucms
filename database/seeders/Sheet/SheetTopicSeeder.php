<?php
namespace Database\Seeders\Sheet;

use App\Models\Academic\Subject;
use App\Models\Sheet\SheetTopic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SheetTopicSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = Subject::all();

        foreach ($subjects as $subject) {
            // Number of topics per subject (you can randomize or fix this)
            $topicCount = rand(3, 7);

            for ($i = 1; $i <= $topicCount; $i++) {
                SheetTopic::create([
                    'subject_id'     => $subject->id,
                    'topic_name'     => $this->generateTopicName($subject->name, $i),
                    'status'         => 'active',
                ]);
            }
        }
    }

    // Generate meaningful topic names
    protected function generateTopicName($subjectName, $index): string
    {
        $topics = [
            'Introduction to',
            'Advanced Concepts of',
            'Understanding',
            'Fundamentals of',
            'Applied',
            'Basics of',
            'Deep Dive into',
            'Modern Approach to',
        ];

        return $topics[array_rand($topics)] . ' ' . Str::title($subjectName) . " - Part $index";
    }
}
