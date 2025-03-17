<?php

namespace App\Models\Academic;

use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shift extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'branch_id'];

    /**
     * Get all students assigned to this shift.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'shift_id');
    }
}
