<?php

namespace App\Observers;

use App\Models\Student\Student;

class StudentObserver
{
    /**
     * Handle the Student "created" event.
     */
    public function created(Student $student): void
    {
        $this->clearCache($student->branch_id);
    }

    /**
     * Handle the Student "updated" event.
     */
    public function updated(Student $student): void
    {
        $this->clearCache($student->branch_id);

        // If branch changed, clear both old and new branch cache
        if ($student->wasChanged('branch_id')) {
            $this->clearCache($student->getOriginal('branch_id'));
        }
    }

    /**
     * Handle the Student "deleted" event.
     */
    public function deleted(Student $student): void
    {
        $this->clearCache($student->branch_id);
    }

    /**
     * Handle the Student "restored" event.
     */
    public function restored(Student $student): void
    {
        $this->clearCache($student->branch_id);
    }

    /**
     * Handle the Student "force deleted" event.
     */
    public function forceDeleted(Student $student): void
    {
        $this->clearCache($student->branch_id);
    }

    /**
     * Clear dashboard cache for student changes
     */
    protected function clearCache(?int $branchId): void
    {
        if (function_exists('clearDashboardStudentCache')) {
            clearDashboardStudentCache($branchId);
        }
    }
}
