<?php
namespace App\Models\Academic;

use App\Models\Sheet\Sheet;
use App\Models\Student\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory; // <--- alias
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassName extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'class_numeral', 'description', 'is_active', 'deleted_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ------------------
     | Local Query Scopes
     |------------------*/

    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query)
    {
        return $query->where('is_active', false);
    }

    public function scopeVisibleFor($query, User $user)
    {
        // Admin sees all branches
        if ($user->isAdmin()) {
            return $query;
        }

        // Manager / Accountant / Others → only own branch students
        return $query->whereHas('students', function ($q) use ($user) {
            $q->where('branch_id', $user->branch_id);
        });
    }

    /* ------------------
     | Helpers
     |------------------*/
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /* ------------------
     | Relationships
     |------------------*/

    // Secondary classes under this regular class
    public function secondaryClasses()
    {
        return $this->hasMany(SecondaryClass::class, 'class_id');
    }

    // Get all subjects associated with this class
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'class_id');
    }

    // Get all sheets associated with this class
    public function sheet()
    {
        return $this->hasOne(Sheet::class, 'class_id'); // explicitly set foreign key
    }

    // Get all the students associated with this class
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    // Get all the active students associated with this class
    public function activeStudents()
    {
        return $this->hasMany(Student::class, 'class_id')->whereHas('studentActivation', fn($q) => $q->where('active_status', 'active'));
    }
    // Get all the inactive students associated with this class
    public function inactiveStudents()
    {
        return $this->hasMany(Student::class, 'class_id')->whereHas('studentActivation', fn($q) => $q->where('active_status', 'inactive'));
    }

    // Get who deleted the class
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
