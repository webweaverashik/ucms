<?php
namespace App\Models\Payment;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentInvoiceComment extends Model
{
    protected $fillable = [
        'payment_invoice_id',
        'comment',
        'commented_by',
    ];

    /**
     * Comment belongs to an invoice
     */
    public function paymentInvoice(): BelongsTo
    {
        return $this->belongsTo(PaymentInvoice::class);
    }

    /**
     * Comment author (optional)
     */
    public function commentedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commented_by');
    }
}
