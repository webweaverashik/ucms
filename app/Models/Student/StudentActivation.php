<?php

namespace App\Models\Student;

use App\Models\User;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentActivation extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'active_status',
        'reason',
        'created_by',
    ];

    // Get the student of this activation record
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // Get the user who created this activation record
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
