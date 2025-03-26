<?php

namespace App\Models\Payment;

use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'payment_style',
        'deadline',
        'tuition_fee',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
