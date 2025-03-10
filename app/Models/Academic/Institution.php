<?php

namespace App\Models\Academic;

use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'eiin_number',
        'type',
        'deleted_by',
    ];

    /**
     * Get the students associated with the institution.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'institution_id');
    }
}
