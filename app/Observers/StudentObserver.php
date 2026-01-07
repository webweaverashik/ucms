<?php
namespace App\Observers;

use App\Models\Student\Student;
use App\Models\Student\StudentClassChangeHistory;

class StudentObserver
{
    /**
     * Handle the Student "updating" event.
     */
    public function updating(Student $student): void
    {
        // Only log when class_id is actually changed
        if (! $student->isDirty('class_id')) {
            return;
        }

        $fromClassId = $student->getOriginal('class_id');
        $toClassId   = $student->class_id;

        // Safety check
        if (! $fromClassId || $fromClassId == $toClassId) {
            return;
        }

        StudentClassChangeHistory::create([
            'student_id'    => $student->id,
            'from_class_id' => $fromClassId,
            'to_class_id'   => $toClassId,
            'created_by'    => auth()->id(),
        ]);
    }
}
