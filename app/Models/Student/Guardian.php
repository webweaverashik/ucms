<?php

namespace App\Models\Student;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guardian extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'mobile_number', 'gender', 'relationship', 'password', 'student_id', 'deleted_by'];

    /**
     * Get the student associated with this guardian.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the user who deleted the guardian (if applicable).
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
