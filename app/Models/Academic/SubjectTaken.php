<?php

namespace App\Models\Academic;

use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubjectTaken extends Model
{
    use HasFactory;
    
    protected $table = 'subjects_taken';

    protected $fillable = ['student_id', 'subject_id', 'is_4th_subject'];

    // Get all students who have taken this subject
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // Get the subject details
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
