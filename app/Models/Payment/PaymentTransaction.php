<?php
namespace App\Models\Payment;

use App\Models\Student\Student;
use App\Models\User;
use App\Models\UserWalletLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'student_classname',
        'payment_invoice_id',
        'payment_type',
        'amount_paid',
        'remaining_amount',
        'voucher_no',
        'created_by',
        'remarks',
        'is_approved',
    ];

    protected $casts = [
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

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function walletLog()
    {
        return $this->hasOne(UserWalletLog::class, 'payment_transaction_id');
    }
}
