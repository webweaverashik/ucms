<?php
namespace App\Models\Student;

use App\Models\User;
use App\Models\Branch;
use App\Models\Academic\Batch;
use App\Models\Payment\Payment;
use App\Models\Academic\ClassName;
use App\Models\Sheet\SheetPayment;
use App\Models\Academic\Institution;
use App\Models\Academic\SubjectTaken;
use App\Models\Sheet\SheetTopicTaken;
use App\Models\Payment\PaymentInvoice;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student\StudentAttendance;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Payment\PaymentTransaction;
use App\Models\Payment\SecondaryClassPayment;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['student_unique_id', 'branch_id', 'name', 'date_of_birth', 'gender', 'class_id', 'academic_group', 'batch_id', 'institution_id', 'religion', 'blood_group', 'home_address', 'email', 'password', 'reference_id', 'student_activation_id', 'photo_url', 'remarks', 'created_by', 'deleted_by'];

    protected $hidden = ['password'];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /* --------------------------------
     | Query Scopes (Canonical Rules)
     |--------------------------------*/

    /**
     * Active students
     * A student is active ONLY if there exists
     * an activation record with active_status = active
     */
    public function scopeActive($query)
    {
        return $query->whereHas('studentActivation', function ($q) {
            $q->where('active_status', 'active');
        });
    }

    /**
     * Pending / inactive students
     * Students who do NOT have ANY active activation in history
     */
    public function scopePending($query)
    {
        return $query->whereNull('student_activation_id');
    }

    /**
     * Scope: Restrict students by authenticated user's branch
     *
     * Rule:
     * - If the authenticated user is an Admin → NO branch restriction applied
     * - If the authenticated user is NOT an Admin → only students
     *   belonging to the user's branch will be returned
     *
     * Usage:
     * Student::pending()->forUserBranch()->count();
     * Student::active()->forUserBranch()->get();
     */
    public function scopeForUserBranch(Builder $query)
    {
        // Get the currently authenticated user
        $user = auth()->user();

        // Safety check: ensure user exists and is NOT an admin
        if ($user && ! $user->isAdmin()) {
            // Restrict query to the user's branch only
            $query->where('branch_id', $user->branch_id);
        }

        // Always return the query for chaining
        return $query;
    }

    /**
     * Scope: Students who have at least one Sheet Payment
     *
     * Rule:
     * - Includes both active and pending students
     * - Student must have at least one SheetPayment record
     */
    public function scopeHasSheetPayment(Builder $query)
    {
        return $query->whereHas('sheetPayments');
    }

    /* ------------------
     | Relationships
     |------------------*/

    // Branch
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Academic Class
    public function class ()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    // Student class change history
    public function classChangeHistories()
    {
        return $this->hasMany(StudentClassChangeHistory::class, 'student_id')->latest();
    }

    // Current secondary classes
    public function secondaryClasses()
    {
        return $this->hasMany(StudentSecondaryClass::class, 'student_id');
    }
    public function secondaryClassPayments()
    {
        return $this->hasMany(SecondaryClassPayment::class, 'student_id');
    }

    // Secondary class history
    public function secondaryClassHistories()
    {
        return $this->hasMany(StudentSecondaryClassHistory::class, 'student_id')->latest();
    }

    // Batch
    public function batch()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    // Institution
    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    // Current activation status (single source of truth)
    public function studentActivation()
    {
        return $this->belongsTo(StudentActivation::class, 'student_activation_id');
    }

    // Activation history
    public function activations()
    {
        return $this->hasMany(StudentActivation::class, 'student_id');
    }

    // Guardians
    public function guardians()
    {
        return $this->hasMany(Guardian::class, 'student_id');
    }

    // Reference
    public function reference()
    {
        return $this->belongsTo(Reference::class, 'reference_id');
    }

    // Mobile numbers
    public function mobileNumbers()
    {
        return $this->hasMany(MobileNumber::class, 'student_id');
    }

    // Siblings
    public function siblings()
    {
        return $this->hasMany(Sibling::class, 'student_id');
    }

    // Subjects taken
    public function subjectsTaken()
    {
        return $this->hasMany(SubjectTaken::class, 'student_id');
    }

    // Current payment profile
    public function payments()
    {
        return $this->hasOne(Payment::class, 'student_id');
    }

    // Payment invoices
    public function paymentInvoices()
    {
        return $this->hasMany(PaymentInvoice::class, 'student_id');
    }

    // Payment transactions
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'student_id');
    }

    // Sheet topics taken
    public function sheetsTopicTaken()
    {
        return $this->hasMany(SheetTopicTaken::class, 'student_id');
    }

    // Sheet payments (only Sheet Fee invoices)
    public function sheetPayments()
    {
        return $this->hasMany(SheetPayment::class, 'student_id')->whereHas('invoice.invoiceType', function ($query) {
            $query->where('type_name', 'Sheet Fee');
        });
    }

    // Created by
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Deleted by (admin/manager)
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    // Login activities
    public function loginActivities()
    {
        return $this->hasMany(LoginActivity::class, 'user_id')->where('user_type', 'student');
    }

    // Attendance records
    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class, 'student_id');
    }
}
