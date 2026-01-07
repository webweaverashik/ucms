<?php
namespace App\Models\Academic;

use App\Models\Branch;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $fillable = ['name', 'branch_id', 'day_off'];

    /**
     * Get the branch associated with this batch.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Get all students assigned to this batch.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'batch_id');
    }

    // Get all the active students associated with this batch
    public function activeStudents()
    {
        return $this->hasMany(Student::class, 'batch_id', 'id')->active();
    }
}
