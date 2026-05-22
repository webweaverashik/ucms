<?php
namespace App\Models\Student;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentChangeLog extends Model
{
    protected $fillable = [
        'student_id',
        'field_name',
        'old_value',
        'new_value',
        'updated_by',
    ];

    /**
     * Get the student associated with this change log.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the user who updated the student.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
