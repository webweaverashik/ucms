<?php
namespace App\Models;

use App\Models\Student\Guardian;
use App\Models\Student\Student;
use App\Models\Teacher\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginActivity extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'user_type', 'ip_address', 'user_agent', 'device'];

    // ✅ LoginActivity Model Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'user_id');
    }

    public function guardian()
    {
        return $this->belongsTo(Guardian::class, 'user_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'user_id');
    }

/**
 * Auto-return the correct model instance depending on user_type
 */
    public function actor()
    {
        return match ($this->user_type) {
            'teacher'  => $this->teacher,
            'guardian' => $this->guardian,
            'student'  => $this->student,
            default    => $this->user,
        };
    }

    /* ✔ Now you can do:
    $log = LoginActivity::first();

    Get actual model:
    $actor = $log->actor;

    e.g., Teacher / Guardian / Student / User model instance
    echo $actor->name;
    */
}
