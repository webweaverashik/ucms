<?php
namespace App\Models\Payment;

use App\Models\Sheet\SheetPayment;
use App\Models\Student\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'student_id',
        'total_amount',
        'amount_due',
        'month_year',
        'status',
        'invoice_type',
        'created_by',
        'deleted_by',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
        Get all the sheet payments for this invoice
    */
    public function sheetPayments()
    {
        return $this->hasMany(SheetPayment::class);
    }

}
