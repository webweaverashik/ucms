<?php
namespace App\Models\Student;

use App\Models\Academic\SecondaryClass;
use App\Models\Payment\SecondaryClassPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentSecondaryClass extends Model
{
    protected $table = 'student_secondary_classes';

    protected $fillable = ['student_id', 'secondary_class_id', 'amount', 'enrolled_at', 'is_active'];

    protected $casts = [
        'enrolled_at' => 'date',
        'is_active'   => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
    ];

    /* ------------------
    | Relationships
    |------------------*/

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function secondaryClass()
    {
        return $this->belongsTo(SecondaryClass::class, 'secondary_class_id');
    }

    /**
     * Get all secondary class payments for this enrollment
     */
    public function payments()
    {
        return $this->hasMany(SecondaryClassPayment::class, 'student_id', 'student_id')->where('secondary_class_id', $this->secondary_class_id);
    }

    /* ------------------
    | Scopes
    |------------------*/

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    public function scopeForSecondaryClass(Builder $query, int $secondaryClassId): Builder
    {
        return $query->where('secondary_class_id', $secondaryClassId);
    }

    /* ------------------
    | Helpers
    |------------------*/

    public function fee(): int
    {
        return (int) $this->amount;
    }

    public function isMonthly(): bool
    {
        return $this->secondaryClass?->payment_type === 'monthly';
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Get total amount paid for this enrollment
     */
    public function getTotalPaidAttribute(): float
    {
        return SecondaryClassPayment::where('student_id', $this->student_id)
            ->where('secondary_class_id', $this->secondary_class_id)
            ->whereHas('invoice')
            ->with(['invoice.paymentTransactions'])
            ->get()
            ->sum(function ($payment) {
                return $payment->invoice?->paymentTransactions->sum('amount_paid') ?? 0;
            });
    }

    /**
     * Check if this enrollment has unpaid invoices
     */
    public function hasUnpaidInvoices(): bool
    {
        return SecondaryClassPayment::where('student_id', $this->student_id)
            ->where('secondary_class_id', $this->secondary_class_id)
            ->whereHas('invoice', function ($q) {
                $q->where('status', '!=', 'paid');
            })
            ->exists();
    }
}
