<?php
namespace App\Console\Commands;

use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Student\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-monthly {--branch= : Filter by branch ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly tuition fee invoices for active students';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting monthly invoice generation...');
        $this->info('===========================================');

        try {
            DB::beginTransaction();

            // Get branch_id from option
            $branchId = $this->option('branch');

            // Get current and last month/year
            $currentMonth  = Carbon::now()->format('m');
            $currentYear   = Carbon::now()->format('Y');
            $lastMonth     = Carbon::now()->subMonth()->format('m');
            $lastMonthYear = Carbon::now()->subMonth()->format('Y');

            $this->info('Billing Period:');
            $this->info("  - Current Month: {$currentMonth}/{$currentYear} (for 'current' payment style)");
            $this->info("  - Last Month: {$lastMonth}/{$lastMonthYear} (for 'due' payment style)");
            $this->newLine();

            // Tuition Fee type
            $invoiceType = PaymentInvoiceType::where('type_name', 'Tuition Fee')->select('id')->first();

            if (! $invoiceType) {
                DB::rollBack();
                $this->error('Tuition Fee invoice type not found. Please create it first.');
                return self::FAILURE;
            }

            // Generate Current Invoices
            $currentResult = $this->generateCurrentInvoices($invoiceType->id, $currentMonth, $currentYear, $branchId);

            // Generate Due Invoices
            $dueResult = $this->generateDueInvoices($invoiceType->id, $lastMonth, $lastMonthYear, $branchId);

            DB::commit();

            // Clearing cache after invoice generation
            $this->clearInvoiceCache();

            // Summary Output
            $this->newLine();
            $this->info('===========================================');
            $this->info('INVOICE GENERATION SUMMARY');
            $this->info('===========================================');

            $totalGenerated = $currentResult['generated'] + $dueResult['generated'];
            $totalSkipped   = $currentResult['skipped'] + $dueResult['skipped'];

            $this->info('Current Invoices:');
            $this->info("  ✓ Generated: {$currentResult['generated']}");
            $this->info("  ⊘ Skipped:   {$currentResult['skipped']} (existing: {$currentResult['skipped_existing']}, free: {$currentResult['skipped_free']})");
            $this->newLine();

            $this->info('Due Invoices:');
            $this->info("  ✓ Generated: {$dueResult['generated']}");
            $this->info("  ⊘ Skipped:   {$dueResult['skipped']} (existing: {$dueResult['skipped_existing']}, free: {$dueResult['skipped_free']})");
            $this->newLine();

            $this->info('Total:');
            $this->info("  ✓ Generated: {$totalGenerated}");
            $this->info("  ⊘ Skipped:   {$totalSkipped}");
            $this->info('===========================================');

            if ($totalGenerated === 0 && $totalSkipped === 0) {
                $this->warn('No active students found for invoice generation.');
            }

            $this->info('[' . now() . '] Invoice generation completed successfully.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto Invoice Generation Error (Command): ' . $e->getMessage());
            $this->error('An error occurred while generating invoices: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Generate invoices for students with 'current' payment style.
     */
    protected function generateCurrentInvoices(int $invoiceTypeId, string $currentMonth, string $currentYear, ?string $branchId = null): array
    {
        $monthYear = "{$currentMonth}_{$currentYear}";

        $this->info("Processing 'current' payment style students for month: {$monthYear}");

        $query = Student::with(['studentActivation', 'payments', 'paymentInvoices', 'branch'])
            ->whereHas('studentActivation', fn($q) => $q->where('active_status', 'active'))
            ->whereHas('payments', fn($q) => $q->where('payment_style', 'current'))
            ->whereHas('class', fn($q) => $q->active());

        // Filter by branch if specified
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $students = $query->get();

        return $this->processStudents($students, $invoiceTypeId, $monthYear, $currentMonth, $currentYear);
    }

    /**
     * Generate invoices for students with 'due' payment style.
     */
    protected function generateDueInvoices(int $invoiceTypeId, string $lastMonth, string $lastMonthYear, ?string $branchId = null): array
    {
        $monthYear = "{$lastMonth}_{$lastMonthYear}";

        $this->info("Processing 'due' payment style students for month: {$monthYear}");

        $query = Student::with(['studentActivation', 'payments', 'paymentInvoices', 'branch'])
            ->whereHas('studentActivation', fn($q) => $q->where('active_status', 'active'))
            ->whereHas('payments', fn($q) => $q->where('payment_style', '!=', 'current'))
            ->whereHas('class', fn($q) => $q->active());

        // Filter by branch if specified
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $students = $query->get();

        return $this->processStudents($students, $invoiceTypeId, $monthYear, $lastMonth, $lastMonthYear);
    }

    /**
     * Process students and generate invoices grouped by branch for logging.
     */
    protected function processStudents($students, int $invoiceTypeId, string $monthYear, string $billingMonth, string $billingYear): array
    {
        $generatedInvoices = 0;
        $skippedReasons    = [
            'existing_invoice' => 0,
            'free_student'     => 0,
        ];

        // Group students by branch for cleaner logging
        $groupedStudents = $students->groupBy('branch.branch_name');

        foreach ($groupedStudents as $branchName => $branchStudents) {
            $this->line("  Processing branch: <fg=cyan>{$branchName}</> ({$branchStudents->count()} students)");

            $bar = $this->output->createProgressBar($branchStudents->count());
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');
            $bar->setMessage('Generating...');
            $bar->start();

            foreach ($branchStudents as $student) {
                // Check if student has zero tuition fee (FREE student)
                if ($student->payments->tuition_fee <= 0) {
                    $skippedReasons['free_student']++;
                    $bar->advance();
                    continue;
                }

                // Check if student already has an invoice for this month_year
                $existingInvoice = $student
                    ->paymentInvoices()
                    ->where('month_year', $monthYear)
                    ->whereHas('invoiceType', function ($q) {
                        $q->where('type_name', 'Tuition Fee');
                    })
                    ->exists();

                if ($existingInvoice) {
                    $skippedReasons['existing_invoice']++;
                    $bar->advance();
                    continue;
                }

                // Get the branch prefix from student's branch
                $branchPrefix = $student->branch->branch_prefix ?? 'DEF';

                // Format for invoice number (e.g., G2506_1001)
                $invoicePrefix = strtoupper($branchPrefix) . substr($billingYear, -2) . $billingMonth . '_';

                // Find the last invoice number with this prefix
                $lastInvoice = PaymentInvoice::where('invoice_number', 'like', $invoicePrefix . '%')
                    ->orderBy('invoice_number', 'desc')
                    ->first();

                $sequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, strlen($invoicePrefix)) + 1 : 1001;

                // Create the new invoice
                PaymentInvoice::create([
                    'invoice_number'  => $invoicePrefix . $sequence,
                    'student_id'      => $student->id,
                    'total_amount'    => $student->payments->tuition_fee,
                    'amount_due'      => $student->payments->tuition_fee,
                    'month_year'      => $monthYear,
                    'invoice_type_id' => $invoiceTypeId,
                    'created_by'      => null, // System-generated invoice
                ]);

                $generatedInvoices++;
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        return [
            'generated'        => $generatedInvoices,
            'skipped'          => array_sum($skippedReasons),
            'skipped_existing' => $skippedReasons['existing_invoice'],
            'skipped_free'     => $skippedReasons['free_student'],
        ];
    }

    /**
     * Clear invoice-related cache for branches.
     */
    protected function clearInvoiceCache(): void
    {
        clearServerCache();
    }
}
