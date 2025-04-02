<?php

namespace App\Models\Academic;

use App\Models\Branch;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'branch_id'];

    /**
     * Get the branch associated with this shift.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    
    /**
     * Get all students assigned to this shift.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'shift_id');
    }

    // Get all the active students associated with this shift
    public function activeStudents()
    {
        return $this->hasMany(Student::class, 'shift_id', 'id')->whereHas('studentActivation', function ($query) {
            $query->where('active_status', 'active');
        });
    }
}
