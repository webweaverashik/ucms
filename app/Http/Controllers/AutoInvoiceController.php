<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Sheet\SheetPayment;
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

    /* -----------------------------
     | Generate Sheet Fee Invoices
     |-----------------------------*/
    public function generateSheet(Request $request)
    {
        $this->authorizeAdmin();

        try {
            DB::beginTransaction();

            $branchId    = $request->input('branch_id');
            $invoiceType = PaymentInvoiceType::where('type_name', 'Sheet Fee')->first();

            if (! $invoiceType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sheet Fee invoice type not found.',
                ], 422);
            }

            $students = Student::active()
                ->with(['branch', 'class.sheet'])
                ->whereHas('class', fn($q) => $q->active()->whereHas('sheet'))
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->get();

            $result = ['generated' => 0, 'existing' => 0, 'no_sheet' => 0];

            foreach ($students as $student) {
                $sheet = $student->class?->sheet;

                if (! $sheet || $sheet->price <= 0) {
                    $result['no_sheet']++;
                    continue;
                }

                $alreadyHasInvoice = SheetPayment::where('student_id', $student->id)
                    ->where('sheet_id', $sheet->id)
                    ->exists();

                if ($alreadyHasInvoice) {
                    $result['existing']++;
                    continue;
                }

                $invoice = PaymentInvoice::create([
                    'invoice_number'  => $this->generateInvoiceNumber($student, now()),
                    'student_id'      => $student->id,
                    'total_amount'    => $sheet->price,
                    'amount_due'      => $sheet->price,
                    // 'month_year'      => now()->format('m_Y'),
                    'invoice_type_id' => $invoiceType->id,
                    'created_by'      => Auth::id(),
                ]);

                SheetPayment::create([
                    'sheet_id'   => $sheet->id,
                    'invoice_id' => $invoice->id,
                    'student_id' => $student->id,
                ]);

                $result['generated']++;
            }

            DB::commit();
            $this->clearInvoiceCache();

            return response()->json([
                'success' => true,
                'message' => $this->buildSheetResultMessage($result),
                'result'  => $result,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto Sheet Invoice Generation Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while generating sheet fee invoices. Please try again.',
            ], 500);
        }
    }

    /* ==========================================================
     | Dry-Run Previews (read-only, no writes)
     |==========================================================*/
    public function previewCurrent(Request $request)
    {
        $this->authorizeAdmin();
        try {
            return response()->json([
                'success' => true,
                'preview' => $this->buildTuitionPreview('current', now(), $request->input('branch_id')),
            ]);
        } catch (\Exception $e) {
            Log::error('Auto Invoice Preview Error (current): ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not generate preview.'], 500);
        }
    }

    public function previewDue(Request $request)
    {
        $this->authorizeAdmin();
        try {
            return response()->json([
                'success' => true,
                'preview' => $this->buildTuitionPreview('due', now()->subMonth(), $request->input('branch_id')),
            ]);
        } catch (\Exception $e) {
            Log::error('Auto Invoice Preview Error (due): ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not generate preview.'], 500);
        }
    }

    public function previewSheet(Request $request)
    {
        $this->authorizeAdmin();
        try {
            return response()->json([
                'success' => true,
                'preview' => $this->buildSheetPreview($request->input('branch_id')),
            ]);
        } catch (\Exception $e) {
            Log::error('Auto Invoice Preview Error (sheet): ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not generate preview.'], 500);
        }
    }

    /* ==========================================================
     | Core Logic (Reusable) — Tuition Fee (Current / Due)
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
            $students    = $this->getEligibleStudents($paymentStyle, $branchId);
            $result      = $this->generateInvoicesForStudents($students, $invoiceType, $monthYear, $billingMonth);

            DB::commit();
            $this->clearInvoiceCache();

            return redirect()->back()->with('success', $this->buildResultMessage($successLabel, $result));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Auto Invoice Generation Error ({$successLabel}): " . $e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while generating invoices. Please try again.');
        }
    }

    /* ==========================================================
     | Preview Builders (read-only)
     |==========================================================*/
    private function buildTuitionPreview(string $paymentStyle, Carbon $billingMonth, ?int $branchId): array
    {
        $monthYear   = $billingMonth->format('m_Y');
        $invoiceType = PaymentInvoiceType::where('type_name', 'Tuition Fee')->first();

        $students = Student::with([
            'studentActivation',
            'payments',
            'class',
            // Only eager-load the specific invoices we need to check — avoids loading full history
            'paymentInvoices' => fn($q) => $q
                ->where('month_year', $monthYear)
                ->when($invoiceType, fn($q) => $q->where('invoice_type_id', $invoiceType->id)),
        ])
        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
        ->get();

        $result = [
            'billing_period' => $billingMonth->format('F Y'),
            'total'          => $students->count(),
            'to_generate'    => 0,
            'skipped'        => [
                'inactive_student'    => 0,
                'inactive_class'      => 0,
                'wrong_payment_style' => 0,
                'no_payment_profile'  => 0,
                'free'                => 0,
                'already_invoiced'    => 0,
            ],
        ];

        foreach ($students as $student) {
            if (! $student->studentActivation || $student->studentActivation->active_status !== 'active') {
                $result['skipped']['inactive_student']++;
                continue;
            }

            if (! $student->class || ! $student->class->is_active) {
                $result['skipped']['inactive_class']++;
                continue;
            }

            if (! $student->payments) {
                $result['skipped']['no_payment_profile']++;
                continue;
            }

            $styleMatch = $paymentStyle === 'current'
                ? $student->payments->payment_style === 'current'
                : $student->payments->payment_style !== 'current';

            if (! $styleMatch) {
                $result['skipped']['wrong_payment_style']++;
                continue;
            }

            if ($student->payments->tuition_fee <= 0) {
                $result['skipped']['free']++;
                continue;
            }

            if ($student->paymentInvoices->isNotEmpty()) {
                $result['skipped']['already_invoiced']++;
                continue;
            }

            $result['to_generate']++;
        }

        $result['total_skipped'] = array_sum($result['skipped']);

        return $result;
    }

    private function buildSheetPreview(?int $branchId): array
    {
        $students = Student::with(['studentActivation', 'class.sheet'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        // Single query to build a "student_id_sheet_id" lookup — avoids N+1
        $existingKeys = array_flip(
            SheetPayment::whereIn('student_id', $students->pluck('id')->all())
                ->get(['student_id', 'sheet_id'])
                ->map(fn($sp) => $sp->student_id . '_' . $sp->sheet_id)
                ->all()
        );

        $result = [
            'billing_period' => now()->format('Y'),
            'total'          => $students->count(),
            'to_generate'    => 0,
            'skipped'        => [
                'inactive_student' => 0,
                'inactive_class'   => 0,
                'no_sheet'         => 0,
                'zero_price'       => 0,
                'already_invoiced' => 0,
            ],
        ];

        foreach ($students as $student) {
            if (! $student->studentActivation || $student->studentActivation->active_status !== 'active') {
                $result['skipped']['inactive_student']++;
                continue;
            }

            if (! $student->class || ! $student->class->is_active) {
                $result['skipped']['inactive_class']++;
                continue;
            }

            $sheet = $student->class->sheet;

            if (! $sheet) {
                $result['skipped']['no_sheet']++;
                continue;
            }

            if ($sheet->price <= 0) {
                $result['skipped']['zero_price']++;
                continue;
            }

            if (isset($existingKeys[$student->id . '_' . $sheet->id])) {
                $result['skipped']['already_invoiced']++;
                continue;
            }

            $result['to_generate']++;
        }

        $result['total_skipped'] = array_sum($result['skipped']);

        return $result;
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
        $result = ['generated' => 0, 'existing' => 0, 'free' => 0];

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

            PaymentInvoice::create([
                'invoice_number'  => $this->generateInvoiceNumber($student, $billingMonth),
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

        $lastInvoice = PaymentInvoice::withTrashed()
            ->where('invoice_number', 'like', $prefix . '%')
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

        if ($result['existing']) $skipped[] = "{$result['existing']} existing invoice(s)";
        if ($result['free'])     $skipped[] = "{$result['free']} FREE student(s)";

        if ($skipped) {
            $message .= ' Skipped: ' . implode(', ', $skipped) . '.';
        }

        return $message;
    }

    private function buildSheetResultMessage(array $result): string
    {
        $message = "Generated {$result['generated']} sheet fee invoice(s).";
        $skipped = [];

        if ($result['existing']) $skipped[] = "{$result['existing']} already invoiced student(s)";
        if ($result['no_sheet']) $skipped[] = "{$result['no_sheet']} student(s) with no sheet or zero price";

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