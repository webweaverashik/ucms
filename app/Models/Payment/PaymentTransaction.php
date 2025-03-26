<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'payment_invoice_id',
        'payment_type',
        'amount_paid',
        'voucher_no',
    ];

    public function paymentInvoice()
    {
        return $this->belongsTo(PaymentInvoice::class);
    }
}

