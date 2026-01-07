<?php
namespace App\Models\Student;

use App\Models\Academic\SecondaryClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StudentSecondaryClassHistory extends Model
{
    protected $fillable = ['student_id', 'secondary_class_id', 'action', 'created_by'];

    /* ------------------
     | Relationships
     |------------------*/

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function secondaryClass()
    {
        return $this->belongsTo(SecondaryClass::class, 'secondary_class_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
