<?php

namespace App\Models\Academic;

use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'academic_group', 'is_mandatory', 'class_id'];

    public function class()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'subjects_taken', 'subject_id', 'student_id')->withPivot('is_4th_subject')->withTimestamps();
    }
}
