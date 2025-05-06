<?php

namespace App\Models;

use App\Models\Academic\Shift;
use App\Models\Student\Student;
use App\Models\Academic\ClassName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = ['branch_name', 'branch_prefix', 'address', 'phone_number'];

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

    // Get all the shifts in the branch
    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }
}
