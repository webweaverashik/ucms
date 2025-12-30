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

    // Get all the active students associated with this class
    public function activeStudents()
    {
        return $this->hasMany(Student::class, 'class_id', 'id')->whereHas('studentActivation', function ($query) {
            $query->where('active_status', 'active');
        });
    }

    // Get all the inactive students associated with this class
    public function inactiveStudents()
    {
        return $this->hasMany(Student::class, 'class_id', 'id')->whereHas('studentActivation', function ($query) {
            $query->where('active_status', 'inactive');
        });
    }

    // Get who deleted the class
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
