<?php
namespace App\Models\Academic;

use App\Models\Sheet\SheetTopic;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'academic_group',
        'subject_type',
        'class_id',
    ];

    protected $casts = [
        'subject_type' => 'string',
    ];

    // Scopes
    public function scopeCompulsory(Builder $query): Builder
    {
        return $query->where('subject_type', 'compulsory');
    }

    public function scopeOptional(Builder $query): Builder
    {
        return $query->where('subject_type', 'optional');
    }

    public function scopeGeneral(Builder $query): Builder
    {
        return $query->where('academic_group', 'General');
    }

    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('academic_group', $group);
    }

    public function scopeByClass(Builder $query, int $classId): Builder
    {
        return $query->where('class_id', $classId);
    }

    // Relationships
    public function class ()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'subjects_taken', 'subject_id', 'student_id')
            ->withPivot('is_4th_subject')
            ->withTimestamps();
    }

    public function sheetTopics()
    {
        return $this->hasMany(SheetTopic::class, 'subject_id');
    }

    // Helpers
    public function isOptional(): bool
    {
        return $this->subject_type === 'optional';
    }

    public function isCompulsory(): bool
    {
        return $this->subject_type === 'compulsory';
    }

    public function isGeneral(): bool
    {
        return $this->academic_group === 'General';
    }
}
