<?php

namespace App\Models;

use App\Models\Student\Student;
use App\Models\Academic\ClassName;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['branch_name', 'address', 'phone_number'];

    /**
     * Get all the users in the branch
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Get all the students in the branch
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    // Get all the teachers in the branch
    // public function teachers()
    // {
    //     return $this->hasMany(Teacher::class);
    // }

    // Get all the classes in the branch
    public function classes()
    {
        return $this->hasMany(ClassName::class);
    }
}
