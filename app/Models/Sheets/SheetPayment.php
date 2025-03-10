<?php

namespace App\Models\Sheets;

use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SheetPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sheet_id',
        'student_id',
        'amount_paid',
    ];

    public function sheet()
    {
        return $this->belongsTo(Sheet::class, 'sheet_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
