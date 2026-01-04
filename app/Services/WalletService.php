<?php
namespace App\Services;

use App\Models\Payment\PaymentTransaction;
use App\Models\User;
use App\Models\UserWalletLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Record a collection (when user collects payment from student).
     * Balance INCREASES.
     */
    public function recordCollection(User $user, float $amount, ?PaymentTransaction $payment = null, ?string $description = null): UserWalletLog
    {
        return $this->processTransaction(user: $user, amount: abs($amount), type: UserWalletLog::TYPE_COLLECTION, paymentId: $payment?->id, description: $description ?? 'Payment collection');
    }

    /**
     * Record a settlement (when admin receives money from user).
     * Balance DECREASES.a
     */
    public function recordSettlement(User $user, float $amount, ?string $description = null): UserWalletLog
    {
        if ($user->current_balance < $amount) {
            throw new \Exception("Insufficient balance. Available: {$user->current_balance}");
        }

        return $this->processTransaction(user: $user, amount: -abs($amount), type: UserWalletLog::TYPE_SETTLEMENT, paymentId: null, description: $description ?? 'Settlement to admin');
    }

    /**
     * Record an adjustment (manual correction).
     */
    public function recordAdjustment(User $user, float $amount, string $reason): UserWalletLog
    {
        return $this->processTransaction(user: $user, amount: $amount, type: UserWalletLog::TYPE_ADJUSTMENT, paymentId: null, description: "Adjustment: {$reason}");
    }

    /**
     * Process the transaction with proper locking.
     */
    protected function processTransaction(User $user, float $amount, string $type, ?int $paymentId, ?string $description): UserWalletLog
    {
        return DB::transaction(function () use ($user, $amount, $type, $paymentId, $description) {
            // Lock user row to prevent race conditions
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            $oldBalance = (float) $user->current_balance;
            $newBalance = $oldBalance + $amount;

            if ($newBalance < 0) {
                throw new \Exception('Operation would result in negative balance.');
            }

            $log = UserWalletLog::create([
                'user_id'                => $user->id,
                'type'                   => $type,
                'old_balance'            => $oldBalance,
                'new_balance'            => $newBalance,
                'amount'                 => $amount,
                'payment_transaction_id' => $paymentId,
                'description'            => $description,
                'created_by'             => Auth::id() ?? $user->id,
            ]);

            $user->current_balance = $newBalance;

            if ($type === UserWalletLog::TYPE_COLLECTION) {
                $user->total_collected += abs($amount);
            } elseif ($type === UserWalletLog::TYPE_SETTLEMENT) {
                $user->total_settled += abs($amount);
            }

            $user->save();

            return $log;
        });
    }

    /**
     * Get wallet summary for a user.
     */
    public function getSummary(User $user): array
    {
        return [
            'current_balance' => $user->current_balance,
            'total_collected' => $user->total_collected,
            'total_settled'   => $user->total_settled,
            'today_collected' => $user->getTodayCollection(),
            'today_settled'   => $user->getTodaySettlement(),
        ];
    }
}
