<?php
namespace App\Observers;

use App\Models\Payment\PaymentTransaction;

class PaymentTransactionObserver
{
    public function created(PaymentTransaction $transaction): void
    {
        $this->clearCache($transaction);
    }

    public function updated(PaymentTransaction $transaction): void
    {
        $this->clearCache($transaction);
    }

    public function deleted(PaymentTransaction $transaction): void
    {
        $this->clearCache($transaction);
    }

    public function restored(PaymentTransaction $transaction): void
    {
        $this->clearCache($transaction);
    }

    protected function clearCache(PaymentTransaction $transaction): void
    {
        if (function_exists('clearDashboardPaymentCache')) {
            clearDashboardPaymentCache($transaction->student?->branch_id);
        }
    }
}
