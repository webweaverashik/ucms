<?php

namespace App\Models\Student;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentGuardian extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'guardian_id', 'relationship', 'is_primary'];

    /**
     * Get the student associated with this guardian.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the guardian associated with this student.
     */
    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }
}
