<?php

namespace App\Observers;

use App\Models\Student\StudentAttendance;

class AttendanceObserver
{
    /**
     * Handle the StudentAttendance "created" event.
     */
    public function created(StudentAttendance $attendance): void
    {
        $this->clearCache($attendance->branch_id);
    }

    /**
     * Handle the StudentAttendance "updated" event.
     */
    public function updated(StudentAttendance $attendance): void
    {
        $this->clearCache($attendance->branch_id);

        // If branch changed, clear both
        if ($attendance->wasChanged('branch_id')) {
            $this->clearCache($attendance->getOriginal('branch_id'));
        }
    }

    /**
     * Handle the StudentAttendance "deleted" event.
     */
    public function deleted(StudentAttendance $attendance): void
    {
        $this->clearCache($attendance->branch_id);
    }

    /**
     * Clear dashboard attendance cache
     */
    protected function clearCache(?int $branchId): void
    {
        if (function_exists('clearDashboardAttendanceCache')) {
            clearDashboardAttendanceCache($branchId);
        }
    }
}
