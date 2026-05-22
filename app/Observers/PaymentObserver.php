<?php
namespace App\Observers;

use App\Models\Payment\Payment;
use App\Models\Student\StudentChangeLog;

class PaymentObserver
{
    /**
     * Cache logged changes within the same request to prevent duplicates.
     */
    protected static array $loggedChanges = [];

    /**
     * The fields we want to track with user-friendly labels.
     */
    protected array $trackableFields = [
        'payment_style' => 'Payment Style',
        'due_date'      => 'Payment Due Date',
        'tuition_fee'   => 'Tuition Fee',
    ];

    /**
     * Handle the Payment "updating" event.
     */
    public function updating(Payment $payment): void
    {
        foreach ($this->trackableFields as $field => $label) {
            if ($payment->isDirty($field)) {
                $oldValue = $payment->getOriginal($field);
                $newValue = $payment->$field;

                // Normalize tuition_fee for comparison (e.g. 5000.00 vs 5000)
                if ($field === 'tuition_fee') {
                    if ((float)$oldValue === (float)$newValue) {
                        continue;
                    }
                }

                // Helper comparison to prevent false positives when values represent the same content
                $oldStr = is_string($oldValue) ? trim($oldValue) : (is_null($oldValue) ? '' : (string)$oldValue);
                $newStr = is_string($newValue) ? trim($newValue) : (is_null($newValue) ? '' : (string)$newValue);

                if ($oldStr === $newStr) {
                    continue;
                }

                // Prevent duplicates in the same request lifetime
                $changeKey = $payment->student_id . '_' . $field . '_' . md5($oldStr . '_' . $newStr);
                if (isset(self::$loggedChanges[$changeKey])) {
                    continue;
                }
                self::$loggedChanges[$changeKey] = true;

                StudentChangeLog::create([
                    'student_id' => $payment->student_id,
                    'field_name' => $label,
                    'old_value'  => $oldValue,
                    'new_value'  => $newValue,
                    'updated_by' => auth()->id(),
                ]);
            }
        }
    }
}
