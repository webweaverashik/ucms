<?php

namespace App\Models\Student;

use App\Models\Academic\Institution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sibling extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'siblings';

    protected $fillable = ['full_name', 'class', 'institution_id', 'student_id'];

    // Get the student of the current sibling
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // Get the institution associated with this sibling
    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }
}
