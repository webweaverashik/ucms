<?php
namespace App\Observers;

use App\Models\Student\Student;
use App\Models\Student\StudentClassChangeHistory;
use App\Models\Student\StudentChangeLog;
use App\Models\Academic\ClassName;
use App\Models\Academic\Batch;
use App\Models\Academic\Institution;
use Carbon\Carbon;

class StudentObserver
{
    /**
     * Cache logged changes within the same request to prevent duplicates.
     */
    protected static array $loggedChanges = [];

    /**
     * Cache Class Change History logged within the same request to prevent duplicates.
     */
    protected static array $loggedClassHistoryChanges = [];

    /**
     * The fields we want to track with user-friendly labels.
     */
    protected array $trackableFields = [
        'name'            => 'Name',
        'date_of_birth'   => 'Date of Birth',
        'gender'          => 'Gender',
        'branch_id'       => 'Branch',
        'class_id'        => 'Class',
        'academic_group'  => 'Academic Group',
        'batch_id'        => 'Batch',
        'institution_id'  => 'Institution',
        'religion'        => 'Religion',
        'blood_group'     => 'Blood Group',
        'home_address'    => 'Home Address',
        'email'           => 'Email',
        'remarks'         => 'Remarks',
    ];

    /**
     * Handle the "updating" event for Student and other observed models.
     */
    public function updating($model): void
    {
        if ($model instanceof Student) {
            $this->handleStudentUpdating($model);
        } elseif ($model instanceof \App\Models\Student\Guardian) {
            $this->handleGuardianUpdating($model);
        } elseif ($model instanceof \App\Models\Student\Sibling) {
            $this->handleSiblingUpdating($model);
        } elseif ($model instanceof \App\Models\Student\MobileNumber) {
            $this->handleMobileNumberUpdating($model);
        }
    }

    /**
     * Handle the "created" event for observed models.
     */
    public function created($model): void
    {
        if ($model instanceof \App\Models\Student\Guardian) {
            $this->handleGuardianCreated($model);
        } elseif ($model instanceof \App\Models\Student\Sibling) {
            $this->handleSiblingCreated($model);
        } elseif ($model instanceof \App\Models\Student\MobileNumber) {
            $this->handleMobileNumberCreated($model);
        }
    }

    /**
     * Handle the "deleted" event for observed models.
     */
    public function deleted($model): void
    {
        if ($model instanceof \App\Models\Student\Guardian) {
            $this->handleGuardianDeleted($model);
        } elseif ($model instanceof \App\Models\Student\Sibling) {
            $this->handleSiblingDeleted($model);
        } elseif ($model instanceof \App\Models\Student\MobileNumber) {
            $this->handleMobileNumberDeleted($model);
        }
    }

    /**
     * Handle the Student "updating" event.
     */
    protected function handleStudentUpdating(Student $student): void
    {
        // 1. Maintain existing Class Change History logic
        if ($student->isDirty('class_id')) {
            $fromClassId = $student->getOriginal('class_id');
            $toClassId   = $student->class_id;

            if ($fromClassId && $fromClassId != $toClassId) {
                $changeKey = $student->id . '_' . $fromClassId . '_' . $toClassId;
                if (!isset(self::$loggedClassHistoryChanges[$changeKey])) {
                    self::$loggedClassHistoryChanges[$changeKey] = true;
                    StudentClassChangeHistory::create([
                        'student_id'    => $student->id,
                        'from_class_id' => $fromClassId,
                        'to_class_id'   => $toClassId,
                        'created_by'    => auth()->id(),
                    ]);
                }
            }
        }

        // 2. Track updates and insert into student_change_logs table
        foreach ($this->trackableFields as $field => $label) {
            if ($student->isDirty($field)) {
                $oldValue = $student->getOriginal($field);
                $newValue = $student->$field;

                // Make raw DB values human-readable
                if ($field === 'class_id') {
                    $oldValue = ClassName::withTrashed()->find($oldValue)?->name ?? $oldValue;
                    $newValue = ClassName::withTrashed()->find($newValue)?->name ?? $newValue;
                } elseif ($field === 'batch_id') {
                    $oldValue = Batch::find($oldValue)?->name ?? $oldValue;
                    $newValue = Batch::find($newValue)?->name ?? $newValue;
                } elseif ($field === 'branch_id') {
                    $oldValue = \App\Models\Branch::find($oldValue)?->branch_name ?? $oldValue;
                    $newValue = \App\Models\Branch::find($newValue)?->branch_name ?? $newValue;
                } elseif ($field === 'institution_id') {
                    $oldValue = Institution::find($oldValue)?->name ?? $oldValue;
                    $newValue = Institution::find($newValue)?->name ?? $newValue;
                } elseif ($field === 'date_of_birth') {
                    try {
                        $oldValue = $oldValue ? Carbon::parse($oldValue)->format('d-M-Y') : null;
                        $newValue = $newValue ? Carbon::parse($newValue)->format('d-M-Y') : null;
                    } catch (\Exception $e) {
                        // Suppress parse errors and keep raw database string
                    }
                }

                // Helper comparison to prevent false positives when values represent the same content
                $oldStr = is_string($oldValue) ? trim($oldValue) : (is_null($oldValue) ? '' : (string)$oldValue);
                $newStr = is_string($newValue) ? trim($newValue) : (is_null($newValue) ? '' : (string)$newValue);

                if ($oldStr === $newStr) {
                    continue;
                }

                // Prevent duplicates in the same request lifetime
                $changeKey = $student->id . '_' . $field . '_' . md5($oldStr . '_' . $newStr);
                if (isset(self::$loggedChanges[$changeKey])) {
                    continue;
                }
                self::$loggedChanges[$changeKey] = true;

                StudentChangeLog::create([
                    'student_id' => $student->id,
                    'field_name' => $label,
                    'old_value'  => $oldValue,
                    'new_value'  => $newValue,
                    'updated_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Handle the Guardian "created" event.
     */
    protected function handleGuardianCreated($guardian): void
    {
        $relation = ucfirst($guardian->relationship ?? 'Guardian');
        $details = [];
        if ($guardian->name) $details[] = "Name: " . $guardian->name;
        if ($guardian->mobile_number) $details[] = "Mobile: " . $guardian->mobile_number;
        if ($guardian->gender) $details[] = "Gender: " . ucfirst($guardian->gender);

        $newValue = implode(', ', $details) ?: 'Added';

        StudentChangeLog::create([
            'student_id' => $guardian->student_id,
            'field_name' => "Guardian ($relation) Joined",
            'old_value'  => '-',
            'new_value'  => $newValue,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the Guardian "updating" event.
     */
    protected function handleGuardianUpdating($guardian): void
    {
        $fields = [
            'name'          => 'Name',
            'mobile_number' => 'Mobile Number',
            'gender'        => 'Gender',
            'relationship'  => 'Relationship',
        ];

        $relation = ucfirst($guardian->getOriginal('relationship') ?? $guardian->relationship ?? 'Guardian');

        foreach ($fields as $field => $label) {
            if ($guardian->isDirty($field)) {
                $oldValue = $guardian->getOriginal($field);
                $newValue = $guardian->$field;

                $oldStr = is_string($oldValue) ? trim($oldValue) : (is_null($oldValue) ? '' : (string)$oldValue);
                $newStr = is_string($newValue) ? trim($newValue) : (is_null($newValue) ? '' : (string)$newValue);

                if ($oldStr === $newStr) {
                    continue;
                }

                if ($field === 'gender') {
                    $oldValue = $oldValue ? ucfirst($oldValue) : $oldValue;
                    $newValue = $newValue ? ucfirst($newValue) : $newValue;
                }

                $changeKey = $guardian->student_id . '_guardian_' . $guardian->id . '_' . $field . '_' . md5($oldStr . '_' . $newStr);
                if (isset(self::$loggedChanges[$changeKey])) {
                    continue;
                }
                self::$loggedChanges[$changeKey] = true;

                StudentChangeLog::create([
                    'student_id' => $guardian->student_id,
                    'field_name' => "Guardian ($relation) $label",
                    'old_value'  => $oldValue,
                    'new_value'  => $newValue,
                    'updated_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Handle the Guardian "deleted" event.
     */
    protected function handleGuardianDeleted($guardian): void
    {
        $relation = ucfirst($guardian->relationship ?? 'Guardian');
        $oldValue = $guardian->name ?: 'Guardian';

        StudentChangeLog::create([
            'student_id' => $guardian->student_id,
            'field_name' => "Guardian ($relation) Removed",
            'old_value'  => $oldValue,
            'new_value'  => 'Removed',
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the Sibling "created" event.
     */
    protected function handleSiblingCreated($sibling): void
    {
        $relation = ucfirst($sibling->relationship ?? 'Sibling');
        $details = [];
        if ($sibling->name) $details[] = "Name: " . $sibling->name;
        if ($sibling->class) $details[] = "Class: " . $sibling->class;
        if ($sibling->institution_name) $details[] = "School: " . $sibling->institution_name;

        $newValue = implode(', ', $details) ?: 'Added';

        StudentChangeLog::create([
            'student_id' => $sibling->student_id,
            'field_name' => "Sibling ($relation) Added",
            'old_value'  => '-',
            'new_value'  => $newValue,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the Sibling "updating" event.
     */
    protected function handleSiblingUpdating($sibling): void
    {
        $fields = [
            'name'             => 'Name',
            'year'             => 'Year',
            'class'            => 'Class',
            'institution_name' => 'Institution',
            'relationship'     => 'Relationship',
        ];

        $relation = ucfirst($sibling->getOriginal('relationship') ?? $sibling->relationship ?? 'Sibling');

        foreach ($fields as $field => $label) {
            if ($sibling->isDirty($field)) {
                $oldValue = $sibling->getOriginal($field);
                $newValue = $sibling->$field;

                $oldStr = is_string($oldValue) ? trim($oldValue) : (is_null($oldValue) ? '' : (string)$oldValue);
                $newStr = is_string($newValue) ? trim($newValue) : (is_null($newValue) ? '' : (string)$newValue);

                if ($oldStr === $newStr) {
                    continue;
                }

                $changeKey = $sibling->student_id . '_sibling_' . $sibling->id . '_' . $field . '_' . md5($oldStr . '_' . $newStr);
                if (isset(self::$loggedChanges[$changeKey])) {
                    continue;
                }
                self::$loggedChanges[$changeKey] = true;

                StudentChangeLog::create([
                    'student_id' => $sibling->student_id,
                    'field_name' => "Sibling ($relation) $label",
                    'old_value'  => $oldValue,
                    'new_value'  => $newValue,
                    'updated_by' => auth()->id(),
                ]);
            }
        }
    }

    /**
     * Handle the Sibling "deleted" event.
     */
    protected function handleSiblingDeleted($sibling): void
    {
        $relation = ucfirst($sibling->relationship ?? 'Sibling');
        $oldValue = $sibling->name ?: 'Sibling';

        StudentChangeLog::create([
            'student_id' => $sibling->student_id,
            'field_name' => "Sibling ($relation) Removed",
            'old_value'  => $oldValue,
            'new_value'  => 'Removed',
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the MobileNumber "created" event.
     */
    protected function handleMobileNumberCreated($mobileNumber): void
    {
        $type = strtoupper($mobileNumber->number_type ?? 'SMS');
        StudentChangeLog::create([
            'student_id' => $mobileNumber->student_id,
            'field_name' => "Phone ($type)",
            'old_value'  => '-',
            'new_value'  => $mobileNumber->mobile_number,
            'updated_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the MobileNumber "updating" event.
     */
    protected function handleMobileNumberUpdating($mobileNumber): void
    {
        if ($mobileNumber->isDirty('mobile_number')) {
            $oldValue = $mobileNumber->getOriginal('mobile_number');
            $newValue = $mobileNumber->mobile_number;

            $oldStr = is_string($oldValue) ? trim($oldValue) : (is_null($oldValue) ? '' : (string)$oldValue);
            $newStr = is_string($newValue) ? trim($newValue) : (is_null($newValue) ? '' : (string)$newValue);

            if ($oldStr === $newStr) {
                return;
            }

            $type = strtoupper($mobileNumber->number_type ?? 'SMS');

            $changeKey = $mobileNumber->student_id . '_mobile_' . $type . '_' . md5($oldStr . '_' . $newStr);
            if (isset(self::$loggedChanges[$changeKey])) {
                return;
            }
            self::$loggedChanges[$changeKey] = true;

            StudentChangeLog::create([
                'student_id' => $mobileNumber->student_id,
                'field_name' => "Phone ($type)",
                'old_value'  => $oldValue,
                'new_value'  => $newValue,
                'updated_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the MobileNumber "deleted" event.
     */
    protected function handleMobileNumberDeleted($mobileNumber): void
    {
        $type = strtoupper($mobileNumber->number_type ?? 'SMS');
        StudentChangeLog::create([
            'student_id' => $mobileNumber->student_id,
            'field_name' => "Phone ($type)",
            'old_value'  => $mobileNumber->mobile_number,
            'new_value'  => 'Removed',
            'updated_by' => auth()->id(),
        ]);
    }


}
