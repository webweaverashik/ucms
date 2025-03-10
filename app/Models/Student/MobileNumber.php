<?php

namespace App\Models\Student;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MobileNumber extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['mobile_number', 'number_type', 'student_id', 'deleted_by'];

    
    // Get the student that owns the mobile number.
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
