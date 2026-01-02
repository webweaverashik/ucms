<?php
namespace App\Console\Commands;

use App\Models\Payment\PaymentTransaction;
use App\Models\User;
use App\Models\UserWalletLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigratePaymentsToWalletLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:migrate-payments
                            {--fresh : Reset all wallet data before migrating}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing payment transactions to user wallet logs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isFresh  = $this->option('fresh');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Confirm before proceeding
        if (! $isDryRun && ! $this->confirm('This will create wallet logs from existing payment transactions. Continue?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Fresh start - reset all wallet data
        if ($isFresh && ! $isDryRun) {
            if ($this->confirm('⚠️  This will DELETE all existing wallet logs and reset balances. Are you sure?', false)) {
                $this->resetWalletData();
            } else {
                $this->info('Fresh reset cancelled. Proceeding with append mode...');
            }
        }

        $this->info('📊 Fetching payment transactions...');

        // Get all payment transactions ordered by date
        $transactions = PaymentTransaction::with(['student', 'createdBy'])
            ->whereNotNull('created_by')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($transactions->isEmpty()) {
            $this->warn('No payment transactions found.');
            return 0;
        }

        $this->info("Found {$transactions->count()} payment transactions.");
        $this->newLine();

        $bar = $this->output->createProgressBar($transactions->count());
        $bar->start();

        $successCount = 0;
        $errorCount   = 0;
        $skippedCount = 0;
        $errors       = [];

        // Track balances per user
        $userBalances = [];

        foreach ($transactions as $payment) {
            try {
                $userId = $payment->created_by;

                // Skip if user doesn't exist
                if (! $payment->createdBy) {
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Check if already migrated (by payment_transaction_id)
                $exists = UserWalletLog::where('payment_transaction_id', $payment->id)->exists();
                if ($exists) {
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Initialize user balance tracking
                if (! isset($userBalances[$userId])) {
                    $user                  = User::find($userId);
                    $userBalances[$userId] = [
                        'current_balance' => (float) ($user->current_balance ?? 0),
                        'total_collected' => (float) ($user->total_collected ?? 0),
                        'model'           => $user,
                    ];
                }

                $amount     = (float) $payment->amount_paid;
                $oldBalance = $userBalances[$userId]['current_balance'];
                $newBalance = $oldBalance + $amount;

                if (! $isDryRun) {
                    DB::transaction(function () use ($payment, $userId, $oldBalance, $newBalance, $amount, &$userBalances) {
                        // Create wallet log
                        UserWalletLog::create([
                            'user_id'                => $userId,
                            'type'                   => UserWalletLog::TYPE_COLLECTION,
                            'old_balance'            => $oldBalance,
                            'new_balance'            => $newBalance,
                            'amount'                 => $amount,
                            'payment_transaction_id' => $payment->id,
                            'description'            => "Collection from Student #{$payment->student->student_unique_id} for Invoice #{$payment->paymentInvoice->invoice_number} (Voucher #{$payment->voucher_no})",
                            'created_by' => $userId,
                            'created_at' => $payment->created_at,
                        ]);

                        // Update tracking
                        $userBalances[$userId]['current_balance'] = $newBalance;
                        $userBalances[$userId]['total_collected'] += $amount;
                    });
                } else {
                    // Dry run - just track
                    $userBalances[$userId]['current_balance'] = $newBalance;
                    $userBalances[$userId]['total_collected'] += $amount;
                }

                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Payment #{$payment->id}: " . $e->getMessage();
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Update user balances
        if (! $isDryRun) {
            $this->info('💰 Updating user balances...');

            foreach ($userBalances as $userId => $data) {
                User::where('id', $userId)->update([
                    'current_balance' => $data['current_balance'],
                    'total_collected' => $data['total_collected'],
                ]);
            }
        }

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('           MIGRATION SUMMARY            ');
        $this->info('═══════════════════════════════════════');
        $this->info("✅ Successfully migrated: {$successCount}");
        $this->info("⏭️  Skipped (already exists): {$skippedCount}");
        $this->info("❌ Errors: {$errorCount}");
        $this->info('👥 Users affected: ' . count($userBalances));
        $this->newLine();

        // Show user balances summary
        $this->info('📊 User Balances Summary:');
        $this->table(
            ['User ID', 'Name', 'Total Collected', 'Current Balance'],
            collect($userBalances)
                ->map(function ($data, $userId) {
                    return [$userId, $data['model']->name ?? 'Unknown', '৳' . number_format($data['total_collected'], 2), '৳' . number_format($data['current_balance'], 2)];
                })
                ->toArray(),
        );

        if (! empty($errors)) {
            $this->newLine();
            $this->error('Errors encountered:');
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->line("  - {$error}");
            }
            if (count($errors) > 10) {
                $this->line('  ... and ' . (count($errors) - 10) . ' more errors');
            }
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn('🔍 This was a DRY RUN. No changes were made.');
            $this->info('Run without --dry-run to apply changes.');
        }

        return 0;
    }

    /**
     * Reset all wallet data.
     */
    protected function resetWalletData(): void
    {
        $this->info('🗑️  Resetting wallet data...');

        // Delete all wallet logs
        UserWalletLog::query()->delete();
        $this->line('  - Deleted all wallet logs');

        // Reset user balances
        User::query()->update([
            'current_balance' => 0,
            'total_collected' => 0,
            'total_settled'   => 0,
        ]);
        $this->line('  - Reset all user balances');

        $this->info('✅ Wallet data reset complete.');
        $this->newLine();
    }
}
