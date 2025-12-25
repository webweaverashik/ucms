<?php
namespace App\Observers;

use App\Models\Payment\PaymentInvoice;

class PaymentInvoiceObserver
{
    public function created(PaymentInvoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    public function updated(PaymentInvoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    public function deleted(PaymentInvoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    public function restored(PaymentInvoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    protected function clearCache(PaymentInvoice $invoice): void
    {
        if (function_exists('clearDashboardPaymentCache')) {
            clearDashboardPaymentCache($invoice->student?->branch_id);
        }
    }
}
