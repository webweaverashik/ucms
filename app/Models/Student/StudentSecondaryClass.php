<?php
namespace App\Models\Student;

use App\Models\Academic\SecondaryClass;
use Illuminate\Database\Eloquent\Model;

class StudentSecondaryClass extends Model
{
    protected $table = 'student_secondary_classes';

    protected $fillable = ['student_id', 'secondary_class_id', 'amount', 'enrolled_at'];

    protected $casts = [
        'enrolled_at' => 'date',
    ];
    
    /* ------------------
    | Relationships
    |------------------*/

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function secondaryClass()
    {
        return $this->belongsTo(SecondaryClass::class, 'secondary_class_id');
    }

    /* ------------------
    | Helpers
    |------------------*/

    public function fee(): int
    {
        return $this->amount;
    }

    public function isMonthly(): bool
    {
        return $this->secondaryClass?->payment_type === 'monthly';
    }
}
