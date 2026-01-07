<?php
namespace App\Models\Student;

use App\Models\Academic\ClassName;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StudentClassChangeHistory extends Model
{
    protected $fillable = ['student_id', 'from_class_id', 'to_class_id', 'created_by'];

    /* ------------------
     | Relationships
     |------------------*/

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fromClass()
    {
        return $this->belongsTo(ClassName::class, 'from_class_id')->withTrashed();
    }

    public function toClass()
    {
        return $this->belongsTo(ClassName::class, 'to_class_id')->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
