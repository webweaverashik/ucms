<?php
namespace App\Models\Academic;

use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectTaken extends Model
{
    use HasFactory;

    protected $table = 'subjects_taken';

    protected $fillable = [
        'student_id',
        'subject_id',
        'is_4th_subject',
    ];

    protected $casts = [
        'is_4th_subject' => 'boolean',
    ];

    // Scopes
    public function scopeMainSubjects(Builder $query): Builder
    {
        return $query->where('is_4th_subject', false);
    }

    public function scopeFourthSubjects(Builder $query): Builder
    {
        return $query->where('is_4th_subject', true);
    }

    public function scopeByStudent(Builder $query, int $studentId): Builder
    {
        return $query->where('student_id', $studentId);
    }

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    // Helpers
    public function isFourthSubject(): bool
    {
        return $this->is_4th_subject === true;
    }

    public function isMainSubject(): bool
    {
        return $this->is_4th_subject === false;
    }
}
