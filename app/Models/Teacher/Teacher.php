<?php
namespace App\Models\Teacher;

use App\Models\Academic\ClassName;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Teacher extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'phone', 'email', 'password', 'photo_url', 'base_salary', 'gender', 'academic_qualification', 'experience', 'blood_group', 'is_active', 'deleted_by'];

    protected $casts = [
        'base_salary' => 'integer', // Ensures salary is always treated as an integer
        'is_active'   => 'boolean',
    ];

    /**
     * Get the user who deleted this teacher (if applicable).
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Direct access to the pivot rows (TeacherAssignment model)
     */
    public function assignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /**
     * Classes this teacher is assigned to.
     * Pivot includes id, branch_id, batch_id, subject_id and timestamps.
     * Pivot will be accessible via ->assignment
     */
    public function classes()
    {
        return $this->belongsToMany(ClassName::class, 'teacher_assignments', 'teacher_id', 'class_id')
            ->withPivot(['id', 'branch_id', 'batch_id', 'subject_id', 'created_at', 'updated_at'])
            ->withTimestamps()
            ->as('assignment');
    }

    /**
     * Branches this teacher is assigned to.
     * Pivot accessible via ->assignment (same alias).
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'teacher_assignments', 'teacher_id', 'branch_id')
            ->withPivot(['id', 'class_id', 'batch_id', 'subject_id', 'created_at', 'updated_at'])
            ->withTimestamps()
            ->as('assignment');
    }

    /**
     * Optional convenience: batches via assignments (distinct)
     */
    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'teacher_assignments', 'teacher_id', 'batch_id')
            ->withPivot(['id', 'class_id', 'branch_id', 'subject_id', 'created_at', 'updated_at'])
            ->withTimestamps()
            ->as('assignment');
    }

    /**
     * Convenience: subjects teacher is assigned to
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_assignments', 'teacher_id', 'subject_id')
            ->withPivot(['id', 'class_id', 'branch_id', 'batch_id', 'created_at', 'updated_at'])
            ->withTimestamps()
            ->as('assignment');
    }

    /**
     * Helpers: assign/unassign using TeacherAssignment model.
     */

    /**
     * Assign teacher to the given combination (idempotent).
     *
     * @param  array  $data  ['class_id'=>..., 'branch_id'=>..., 'batch_id'=>null, 'subject_id'=>null]
     * @return \App\Models\TeacherAssignment
     */
    public function assign(array $data)
    {
        $payload = [
            'teacher_id' => $this->id,
            'class_id'   => $data['class_id'],
            'branch_id'  => $data['branch_id'],
            'batch_id'   => $data['batch_id'] ?? null,
            'subject_id' => $data['subject_id'] ?? null,
        ];

        return TeacherAssignment::firstOrCreate($payload);
    }

    /**
     * Unassign (soft-delete) a specific assignment row.
     *
     * @param  array  $data
     * @return bool|null
     */
    public function unassign(array $data)
    {
        $query = TeacherAssignment::where('teacher_id', $this->id)->where('class_id', $data['class_id'])->where('branch_id', $data['branch_id']);

        // handle nullable batch/subject filters if provided
        if (array_key_exists('batch_id', $data)) {
            $query->where('batch_id', $data['batch_id']);
        }

        if (array_key_exists('subject_id', $data)) {
            $query->where('subject_id', $data['subject_id']);
        }

        return $query->delete(); // soft-delete if TeacherAssignment uses SoftDeletes
    }

    /**
     * Check if teacher is assigned to a given combination.
     * Accepts partial data (e.g., only class+branch).
     */
    public function isAssigned(array $data): bool
    {
        $query = $this->assignments()->where('class_id', $data['class_id'])->where('branch_id', $data['branch_id']);

        if (array_key_exists('batch_id', $data)) {
            $query->where('batch_id', $data['batch_id']);
        }

        if (array_key_exists('subject_id', $data)) {
            $query->where('subject_id', $data['subject_id']);
        }

        return $query->exists();
    }
}
