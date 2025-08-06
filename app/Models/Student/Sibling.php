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

    protected $fillable = ['name', 'year', 'class', 'institution_name', 'student_id', 'relationship', 'deleted_by'];

    // Get the student of the current sibling
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
