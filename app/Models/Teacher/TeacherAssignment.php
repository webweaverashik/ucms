<?php
namespace App\Models\Teacher;

use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Academic\Subject;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;

class TeacherAssignment extends Model
{
    protected $table = 'teacher_assignments';

    protected $fillable = [
        'teacher_id',
        'branch_id',
        'batch_id',
        'class_id',
        'subject_id',
    ];

    protected $casts = [

    ];

    /**
     * Relationships
     */

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function className()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Scopes
     */
    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForClassBranch($query, $classId, $branchId)
    {
        return $query->where('class_id', $classId)->where('branch_id', $branchId);
    }

    /**
     * Check if this assignment matches provided attributes.
     * Accepts partial attributes and handles nullable fields safely.
     *
     * Example:
     * $assignment->matches([
     *   'teacher_id' => 1,
     *   'class_id' => 3,
     *   'branch_id' => 2,
     * ]);
     */
    public function matches(array $attributes): bool
    {
        foreach (['teacher_id', 'branch_id', 'class_id', 'batch_id', 'subject_id'] as $key) {
            if (! array_key_exists($key, $attributes)) {
                continue;
            }

            // Use loose comparison to allow 'null' vs null differences
            if ($this->{$key} != $attributes[$key]) {
                return false;
            }
        }

        return true;
    }
}
