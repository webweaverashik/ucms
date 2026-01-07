<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoInvoiceController extends Controller
{
    /* -----------------------------
     | Index
     |-----------------------------*/
    public function index()
    {
        $this->authorizeAdmin();

        $branches = Branch::get();
        return view('settings.auto-invoice.index', compact('branches'));
    }

    /* -----------------------------
     | Generate Current Invoices
     |-----------------------------*/
    public function generateCurrent(Request $request)
    {
        return $this->generateInvoices(
            paymentStyle: 'current',
            billingMonth: now(),
            branchId: $request->query('branch_id'),
            successLabel: 'current'
        );
    }

    /* -----------------------------
     | Generate Due Invoices
     |-----------------------------*/
    public function generateDue(Request $request)
    {
        return $this->generateInvoices(
            paymentStyle: 'due',
            billingMonth: now()->subMonth(),
            branchId: $request->query('branch_id'),
            successLabel: 'due'
        );
    }

    /* ==========================================================
     | Core Logic (Reusable)
     |==========================================================*/

    private function generateInvoices(
        string $paymentStyle,
        Carbon $billingMonth,
        ?int $branchId,
        string $successLabel
    ) {
        $this->authorizeAdmin();

        try {
            DB::beginTransaction();

            $monthYear   = $billingMonth->format('m_Y');
            $invoiceType = $this->getInvoiceType();

            $students = $this->getEligibleStudents($paymentStyle, $branchId);

            $result = $this->generateInvoicesForStudents(
                students: $students,
                invoiceType: $invoiceType,
                monthYear: $monthYear,
                billingMonth: $billingMonth
            );

            DB::commit();
            $this->clearInvoiceCache();

            return redirect()->back()->with(
                'success',
                $this->buildResultMessage($successLabel, $result)
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Auto Invoice Generation Error ({$successLabel}): " . $e->getMessage());

            return redirect()->back()->with(
                'error',
                'An error occurred while generating invoices. Please try again.'
            );
        }
    }

    /* ==========================================================
     | Helpers
     |==========================================================*/

    private function getEligibleStudents(string $paymentStyle, ?int $branchId)
    {
        return Student::active()
            ->with(['payments', 'paymentInvoices', 'branch'])
            ->whereHas('payments', function ($q) use ($paymentStyle) {
                $paymentStyle === 'current'
                    ? $q->where('payment_style', 'current')
                    : $q->where('payment_style', '!=', 'current');
            })
            ->whereHas('class', fn($q) => $q->active())
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();
    }

    private function getInvoiceType(): PaymentInvoiceType
    {
        $type = PaymentInvoiceType::where('type_name', 'Tuition Fee')->first();

        if (! $type) {
            throw new \Exception('Tuition Fee invoice type not found.');
        }

        return $type;
    }

    private function generateInvoicesForStudents(
        $students,
        PaymentInvoiceType $invoiceType,
        string $monthYear,
        Carbon $billingMonth
    ): array {
        $result = [
            'generated' => 0,
            'existing'  => 0,
            'free'      => 0,
        ];

        foreach ($students as $student) {
            if ($student->payments->tuition_fee <= 0) {
                $result['free']++;
                continue;
            }

            $exists = $student->paymentInvoices()
                ->where('month_year', $monthYear)
                ->where('invoice_type_id', $invoiceType->id)
                ->exists();

            if ($exists) {
                $result['existing']++;
                continue;
            }

            $invoiceNumber = $this->generateInvoiceNumber(
                $student,
                $billingMonth
            );

            PaymentInvoice::create([
                'invoice_number'  => $invoiceNumber,
                'student_id'      => $student->id,
                'total_amount'    => $student->payments->tuition_fee,
                'amount_due'      => $student->payments->tuition_fee,
                'month_year'      => $monthYear,
                'invoice_type_id' => $invoiceType->id,
                'created_by'      => Auth::id(),
            ]);

            $result['generated']++;
        }

        return $result;
    }

    private function generateInvoiceNumber(Student $student, Carbon $month): string
    {
        $prefix = strtoupper($student->branch->branch_prefix ?? 'DEF')
        . $month->format('ym') . '_';

        $lastInvoice = PaymentInvoice::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->first();

        $sequence = $lastInvoice
            ? (int) substr($lastInvoice->invoice_number, strlen($prefix)) + 1
            : 1001;

        return $prefix . $sequence;
    }

    private function buildResultMessage(string $label, array $result): string
    {
        $message = "Generated {$result['generated']} {$label} invoice(s).";

        $skipped = [];
        if ($result['existing']) {
            $skipped[] = "{$result['existing']} existing invoice(s)";
        }
        if ($result['free']) {
            $skipped[] = "{$result['free']} FREE student(s)";
        }

        if ($skipped) {
            $message .= ' Skipped: ' . implode(', ', $skipped) . '.';
        }

        return $message;
    }

    private function authorizeAdmin()
    {
        if (! auth()->user()->isAdmin()) {
            redirect()->back()->with('warning', 'Unauthorized Access')->send();
            exit;
        }
    }

    private function clearInvoiceCache(): void
    {
        clearServerCache();
    }
}
