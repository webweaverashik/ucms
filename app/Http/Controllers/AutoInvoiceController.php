<?php
namespace App\Http\Controllers;

use App\Models\Payment\PaymentInvoice;
use App\Models\Student\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AutoInvoiceController extends Controller
{
    public function generate()
    {
        // Get current and previous month/year
        $currentMonth = Carbon::now()->format('m');
        $currentYear  = Carbon::now()->format('Y');
        $lastMonth    = Carbon::now()->subMonth()->format('m');

        // Get the branch prefix from authenticated user
        $branchPrefix = Auth::user()->branch->branch_prefix;

        // Format for invoice number (G2506_1001)
        $invoicePrefix = strtoupper($branchPrefix) .
        substr($currentYear, -2) .
            $currentMonth . '_';

        // Find the last invoice number to determine next sequence
        $lastInvoice = PaymentInvoice::where('invoice_number', 'like', $invoicePrefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        $sequence = $lastInvoice ?
        (int) substr($lastInvoice->invoice_number, strlen($invoicePrefix)) + 1 :
        1001;

        // Get all active students with non-zero tuition fees
        $students = Student::with(['studentActivation', 'payments', 'paymentInvoices'])
            ->whereHas('studentActivation', function ($query) {
                $query->where('active_status', 'active');
            })
            ->whereHas('payments', function ($query) {
                $query->where('tuition_fee', '>', 0);
            })
            ->get();

        $generatedInvoices = 0;

        foreach ($students as $student) {
            // Determine month_year based on payment style
            $monthYear = ($student->payments->payment_style === 'current') ?
            "{$currentMonth}_{$currentYear}" :
            "{$lastMonth}_{$currentYear}";

            // Check if student already has an invoice for this month_year
            $existingInvoice = $student->paymentInvoices()
                ->where('month_year', $monthYear)
                ->where('invoice_type', 'tuition_fee')
                ->exists();

            if ($existingInvoice) {
                continue;
            }

            // Create the new invoice
            PaymentInvoice::create([
                'invoice_number' => $invoicePrefix . $sequence,
                'student_id'     => $student->id,
                'total_amount'   => $student->payments->tuition_fee,
                'amount_due'     => $student->payments->tuition_fee,
                'month_year'     => $monthYear,
                'status'         => 'due',
                'invoice_type'   => 'tuition_fee',
                'created_by'     => Auth::id(),
            ]);

            $sequence++;
            $generatedInvoices++;
        }

        return back()->with('success', "Generated {$generatedInvoices} new invoices.");
    }
}
