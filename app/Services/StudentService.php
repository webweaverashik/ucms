<?php
namespace App\Services;

use Carbon\Carbon;
use App\Models\Branch;
use App\Models\Student\Student;
use App\Models\Academic\Subject;
use App\Models\Academic\ClassName;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentInvoiceType;
use App\Models\Payment\SecondaryClassPayment;
use Illuminate\Validation\ValidationException;

class StudentService
{
    /**
     * Validate optional subjects based on class numeral and group
     *
     * NEW FLEXIBLE VALIDATION:
     * - Optional subjects are truly optional (not required)
     * - Only validates that Main ≠ 4th when BOTH are selected
     *
     * @param array $validated
     * @param int $classNumeral
     * @param string $group
     * @return bool|string
     */
    public function validateOptionalSubjects(array $validated, int $classNumeral, string $group): bool | string
    {
        // Only validate for classes 9-12
        if ($classNumeral < 9 || $classNumeral > 12) {
            return true;
        }

        // Check if this class/group has optional subjects
        $hasOptionalSubjects = Subject::where('class_id', $validated['student_class'])->where('academic_group', $group)->where('subject_type', 'optional')->exists();

        if (! $hasOptionalSubjects) {
            return true;
        }

        // FIXED: Check for duplicate main and 4th subjects from the subjects array
        $subjects         = $validated['subjects'] ?? [];
        $fourthSubjectIds = [];
        $mainSubjectIds   = [];

        foreach ($subjects as $subject) {
            $is4th = $this->isFourthSubjectValue($subject['is_4th'] ?? '0');

            if ($is4th) {
                $fourthSubjectIds[] = $subject['id'];
            } else {
                $mainSubjectIds[] = $subject['id'];
            }
        }

        // Check if any 4th subject is also selected as main
        foreach ($fourthSubjectIds as $fourthId) {
            if (in_array($fourthId, $mainSubjectIds)) {
                return 'A subject cannot be both main and 4th subject. Please select different subjects.';
            }
        }

        return true;
    }

    /**
     * Store student subjects - FIXED VERSION
     * Now properly reads the is_4th flag from each subject in the array
     *
     * @param Student $student
     * @param array $validated
     */
    public function storeStudentSubjects(Student $student, array $validated): void
    {
        $subjects = $validated['subjects'] ?? [];

        if (empty($subjects)) {
            return;
        }

        // Track 4th subject count for validation
        $fourthSubjectCount = 0;

        foreach ($subjects as $subjectData) {
            $subjectId = $subjectData['id'];
            $is4th     = $this->isFourthSubjectValue($subjectData['is_4th'] ?? '0');

            if ($is4th) {
                $fourthSubjectCount++;
            }

            // Validate: Only one 4th subject allowed
            if ($fourthSubjectCount > 1) {
                throw ValidationException::withMessages([
                    'subjects' => 'Only one subject can be marked as 4th subject.',
                ]);
            }

            $student->subjectsTaken()->create([
                'subject_id'     => $subjectId,
                'is_4th_subject' => $is4th,
            ]);
        }
    }

    /**
     * Update student subjects with proper is_4th_subject handling
     */
    public function updateStudentSubjects(Student $student, array $subjects): void
    {
        // Validate that there's at most one 4th subject
        $fourthSubjects = collect($subjects)->filter(function ($subject) {
            return $this->isFourthSubjectValue($subject['is_4th']);
        });

        if ($fourthSubjects->count() > 1) {
            throw ValidationException::withMessages([
                'subjects' => 'Only one subject can be marked as 4th subject.',
            ]);
        }

        // Get the 4th subject ID if exists
        $fourthSubjectId = $fourthSubjects->first()['id'] ?? null;

        // Check for duplicate main optional and 4th subject
        if ($fourthSubjectId) {
            $mainSubjects = collect($subjects)
                ->filter(function ($subject) {
                    return ! $this->isFourthSubjectValue($subject['is_4th']);
                })
                ->pluck('id')
                ->toArray();

            if (in_array($fourthSubjectId, $mainSubjects)) {
                throw ValidationException::withMessages([
                    'subjects' => 'A subject cannot be both main and 4th subject.',
                ]);
            }
        }

        // Delete existing subjects
        $student->subjectsTaken()->delete();

        // Create new subject records for each subject
        foreach ($subjects as $subject) {
            $student->subjectsTaken()->create([
                'subject_id'     => $subject['id'],
                'is_4th_subject' => $this->isFourthSubjectValue($subject['is_4th']),
            ]);
        }
    }

    /**
     * Helper to determine if a value represents a 4th subject
     * Handles various input formats: '1', '0', 1, 0, true, false, 'true', 'false'
     *
     * @param mixed $value
     * @return bool
     */
    public function isFourthSubjectValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }
        if (is_string($value)) {
            return $value === '1' || strtolower($value) === 'true';
        }
        return false;
    }

    /**
     * Generate a unique student ID
     *
     * Format: <branch_prefix>-<year><class_numeral><sequential_number>
     *
     * Rules:
     * 1. For class 11-12 (HSC): Academic year runs July-June
     *    - Admitted Jan-June: Use current year (e.g., 26 for 2026)
     *    - Admitted July-Dec: Use next year (e.g., 27 for admissions in July 2026)
     * 2. For class 1-10: Use current calendar year
     * 3. Sequential number is shared across ALL classes with same class_numeral in same branch
     *    (e.g., 'HSC Sci' and 'HSC Com' both with class_numeral=11 share the sequence)
     *
     * @param Branch $branch
     * @param ClassName $class
     * @return string
     */
    public function generateStudentUniqueId(Branch $branch, ClassName $class): string
    {
        $classNumeral = $class->class_numeral;
        $currentYear  = Carbon::now()->format('y');

        // For class 10-12: Use class year_prefix
        // For class 04-09 (and others): Use current year
        if ($classNumeral >= 10 && $classNumeral <= 12) {
            $year = $class->year_prefix ?? $currentYear;
        } else {
            $year = $currentYear;
        }

        // Build the ID pattern prefix
        $pattern = "{$branch->branch_prefix}-{$year}{$classNumeral}";

        // Search across ALL classes with same class_numeral
        // This prevents duplicate IDs when multiple classes share the same numeral
        $maxStudent = Student::where('student_unique_id', 'like', "{$pattern}%")
            ->orderByRaw('CAST(SUBSTRING(student_unique_id, -2) AS UNSIGNED) DESC')
            ->first();

        // Calculate next sequential number
        $nextSequence = 1;
        if ($maxStudent) {
            $lastTwoDigits = substr($maxStudent->student_unique_id, -2);
            $nextSequence  = (int) $lastTwoDigits + 1;
        }

        // Cap at 99 (maximum 2-digit sequence)
        $nextSequence = min($nextSequence, 99);

        // Generate the unique ID
        $studentUniqueId = $pattern . str_pad($nextSequence, 2, '0', STR_PAD_LEFT);

        // Safety check: Ensure uniqueness (in case of edge cases)
        while (Student::where('student_unique_id', $studentUniqueId)->exists()) {
            $nextSequence++;
            if ($nextSequence > 99) {
                throw new \Exception("Maximum student limit (99) reached for pattern: {$pattern}");
            }
            $studentUniqueId = $pattern . str_pad($nextSequence, 2, '0', STR_PAD_LEFT);
        }

        return $studentUniqueId;
    }

    /**
     * Create a payment invoice for a student
     *
     * @param Student $student
     * @param float $amount
     * @param string $typeName - e.g., 'Admission Fee', 'Sheet Fee', 'Special Class Fee'
     * @param string|null $monthYear
     * @return void
     */
    public function createInvoice(Student $student, float $amount, string $typeName, ?string $monthYear = null, ?int $secondaryClassId = null): void
    {
        $yearSuffix = now()->format('y');
        $month      = now()->format('m');
        $prefix     = $student->branch->branch_prefix;

        $lastInvoice = PaymentInvoice::where('invoice_number', 'like', "{$prefix}{$yearSuffix}{$month}_%")
            ->orderBy('invoice_number', 'desc')
            ->withTrashed()
            ->first();

        $nextSequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '_') + 1) + 1 : 1001;

        $invoiceNumber = "{$prefix}{$yearSuffix}{$month}_{$nextSequence}";

        $invoiceType = PaymentInvoiceType::where('type_name', $typeName)->first();

        if (! $invoiceType) {
            \Log::warning("Invoice type '{$typeName}' not found for student {$student->id}");
            return;
        }

        $invoice = PaymentInvoice::create([
            'invoice_number'  => $invoiceNumber,
            'student_id'      => $student->id,
            'total_amount'    => $amount,
            'amount_due'      => $amount,
            'month_year'      => $monthYear,
            'invoice_type_id' => $invoiceType->id,
        ]);

        if ($typeName === 'Special Class Fee' && $secondaryClassId) {
            SecondaryClassPayment::create([
                'student_id'         => $student->id,
                'secondary_class_id' => $secondaryClassId,
                'invoice_id'         => $invoice->id,
            ]);
        }
    }

    /**
     * Build academic group badge HTML
     *
     * @param string|null $academicGroup
     * @return string
     */
    public function buildGroupBadge(?string $academicGroup): string
    {
        if ($academicGroup && $academicGroup !== 'General') {
            $badgeClass =
            [
                'Science'  => 'info',
                'Commerce' => 'primary',
                'Arts'     => 'warning',
            ][$academicGroup] ?? 'secondary';

            return '<span class="badge badge-pill badge-' . $badgeClass . '">' . $academicGroup . '</span>';
        }

        return '<span class="text-muted">-</span>';
    }

    /**
     * Get payment info string
     *
     * @param object|null $payments
     * @return string
     */
    public function getPaymentInfo($payments): string
    {
        $paymentStyle = optional($payments)->payment_style ?? '';
        $dueDate      = optional($payments)->due_date ?? '';

        return $paymentStyle ? ucfirst($paymentStyle) . '-1/' . $dueDate : '';
    }
}
