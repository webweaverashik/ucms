<?php
namespace App\Models\Payment;

use App\Models\Academic\SecondaryClass;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecondaryClassPayment extends Model
{
    protected $fillable = [
        'student_id',
        'secondary_class_id',
        'invoice_id',
    ];

    /* ------------------
    | Relationships
    |------------------*/

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function secondaryClass(): BelongsTo
    {
        return $this->belongsTo(SecondaryClass::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class);
    }
}
