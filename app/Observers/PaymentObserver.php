<?php

namespace App\Observers;

use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;

class PaymentObserver
{
    /**
     * Handle PaymentInvoice events
     */
    public function createdInvoice(PaymentInvoice $invoice): void
    {
        $this->clearPaymentCache($invoice->student?->branch_id);
    }

    public function updatedInvoice(PaymentInvoice $invoice): void
    {
        $this->clearPaymentCache($invoice->student?->branch_id);
    }

    public function deletedInvoice(PaymentInvoice $invoice): void
    {
        $this->clearPaymentCache($invoice->student?->branch_id);
    }

    /**
     * Handle PaymentTransaction events
     */
    public function createdTransaction(PaymentTransaction $transaction): void
    {
        $this->clearPaymentCache($transaction->student?->branch_id);
    }

    public function updatedTransaction(PaymentTransaction $transaction): void
    {
        $this->clearPaymentCache($transaction->student?->branch_id);
    }

    public function deletedTransaction(PaymentTransaction $transaction): void
    {
        $this->clearPaymentCache($transaction->student?->branch_id);
    }

    /**
     * Clear dashboard payment cache
     */
    protected function clearPaymentCache(?int $branchId): void
    {
        if (function_exists('clearDashboardPaymentCache')) {
            clearDashboardPaymentCache($branchId);
        }
    }
}