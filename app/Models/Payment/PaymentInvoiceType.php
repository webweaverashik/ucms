<?php
namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class PaymentInvoiceType extends Model
{
    protected $fillable = ['type_name'];

    public function paymentInvoices()
    {
        return $this->hasMany(PaymentInvoice::class);
    }
}
