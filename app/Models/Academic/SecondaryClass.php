<?php

namespace App\Models\Academic;

use App\Models\Student\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecondaryClass extends Model
{
    use SoftDeletes;

    protected $fillable = ['class_id', 'name', 'payment_type', 'fee_amount', 'is_active', 'deleted_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ------------------ | Relationships | ------------------ */

    // Parent regular class
    public function class()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    // Students currently enrolled (pivot table)
    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_secondary_classes')
            ->withPivot(['status', 'enrolled_at'])
            ->withTimestamps();
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}