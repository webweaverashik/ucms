<?php

namespace App\Models\Payment;

use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'payment_invoice_id',
        'payment_type',
        'amount_paid',
        'voucher_no',
        'remarks',
    ];

    public function paymentInvoice()
    {
        return $this->belongsTo(PaymentInvoice::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

