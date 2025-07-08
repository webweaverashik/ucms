<?php
namespace App\Models\Payment;

use App\Models\Student\Student;
use App\Models\User;
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
        'created_by',
        'remarks',
        'is_approved',
    ];

    protected $casts = [
        // other casts...
        'is_approved' => 'boolean',
    ];

    public function paymentInvoice()
    {
        return $this->belongsTo(PaymentInvoice::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
