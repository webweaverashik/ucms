<?php

namespace App\Models\Student;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guardian extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'mobile_number', 'gender', 'address', 'deleted_by'];

    /**
     * Get the student guardians for this guardian.
     */
    public function studentGuardians()
    {
        return $this->hasMany(StudentGuardian::class);
    }

    /**
     * Get the students associated with this guardian.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_guardians')->withPivot('relationship', 'is_primary')->withTimestamps();
    }
}
