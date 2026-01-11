<?php
namespace App\Observers;

use App\Models\Student\StudentSecondaryClass;
use App\Models\Student\StudentSecondaryClassHistory;

class StudentSecondaryClassObserver
{
    /**
     * When student is enrolled into a secondary class
     */
    public function created(StudentSecondaryClass $enrollment): void
    {
        StudentSecondaryClassHistory::create([
            'student_id'         => $enrollment->student_id,
            'secondary_class_id' => $enrollment->secondary_class_id,
            'action'             => 'enrolled',
            'created_by'         => auth()->id(),
        ]);
    }

    /**
     * When student is withdrawn from a secondary class
     * (row deleted)
     */
    public function deleting(StudentSecondaryClass $enrollment): void
    {
        StudentSecondaryClassHistory::create([
            'student_id'         => $enrollment->student_id,
            'secondary_class_id' => $enrollment->secondary_class_id,
            'action'             => 'dropped',
            'created_by'         => auth()->id(),
        ]);
    }
}
