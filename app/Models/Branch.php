<?php
namespace App\Models;

use App\Models\Academic\ClassName;
use App\Models\Academic\Batch;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function activeStudents()
    {
        return $this->hasMany(Student::class, 'branch_id', 'id')->whereHas('studentActivation', function ($query) {
            $query->where('active_status', 'active');
        });
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

    // Get all the batches in the branch
    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
}
