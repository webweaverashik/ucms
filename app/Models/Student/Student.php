<?php

namespace App\Models\Student;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Branch;
use App\Models\Student\StudentActivation;
use App\Models\Student\StudentGuardian;
use App\Models\Student\Reference;
use App\Models\Student\MobileNumber;
use App\Models\Student\Sibling;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['student_unique_id', 'branch_id', 'full_name', 'date_of_birth', 'gender', 'class_id', 'academic_group', 'shift', 'institution_roll', 'institution_id', 'religion', 'home_address', 'email', 'password', 'reference_id', 'student_activation_id', 'photo_url', 'remarks', 'deleted_by'];

    protected $hidden = ['password'];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // Get the current academic class of this student
    public function class()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    // Get the institution associated with this student
    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    // Get the branch of the student:
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Get the latest activation status of this student:
    public function studentActivation()
    {
        return $this->belongsTo(StudentActivation::class, 'student_activation_id');
    }

    // Get all activation history of a student:
    public function activations()
    {
        return $this->hasMany(StudentActivation::class, 'student_id');
    }

    // Get all guardians of a student:
    public function guardians()
    {
        return $this->hasMany(StudentGuardian::class);
    }

    // Get the student's reference:
    public function reference()
    {
        return $this->morphOne(Reference::class, 'referer');
    }

    // Get all the student's mobile numbers:
    public function mobileNumbers()
    {
        return $this->hasMany(MobileNumber::class);
    }

    // Get all the student's siblings:
    public function siblings()
    {
        return $this->hasMany(Sibling::class);
    }
}
