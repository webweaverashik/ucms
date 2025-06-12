<?php
namespace App\Models\Sheet;

use App\Models\Student\Student;
use App\Models\Payment\PaymentInvoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SheetPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sheet_id',
        'invoice_id',
        'student_id',
    ];

    public function sheet()
    {
        return $this->belongsTo(Sheet::class);
    }

    public function invoice()
    {
        return $this->belongsTo(PaymentInvoice::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
