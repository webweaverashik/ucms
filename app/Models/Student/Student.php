<?php
namespace App\Models\Student;

use App\Models\Academic\ClassName;
use App\Models\Academic\Institution;
use App\Models\Academic\Shift;
use App\Models\Academic\SubjectTaken;
use App\Models\Branch;
use App\Models\Payment\Payment;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use App\Models\Sheets\SheetTaken;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['student_unique_id', 'branch_id', 'name', 'date_of_birth', 'gender', 'class_id', 'academic_group', 'shift_id', 'institution_id', 'religion', 'blood_group', 'home_address', 'email', 'password', 'reference_id', 'student_activation_id', 'photo_url', 'remarks', 'deleted_by'];

    protected $hidden = ['password'];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // Get the branch of the student:
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // Get the current academic class of this student
    public function class()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    // Get the current shift of this student
    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    // Get the institution associated with this student
    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    // Get the latest activation status of this student:
    public function studentActivation()
    {
        return $this->belongsTo(StudentActivation::class, 'student_activation_id');
    }

    // Get all activation history of a student:
    public function activations()
    {
        return $this->hasMany(StudentActivation::class, 'student_id');
    }

    // Get all guardians of a student:
    public function guardians()
    {
        return $this->hasMany(Guardian::class, 'student_id');
    }

    // Get the student's reference:
    public function reference()
    {
        return $this->belongsTo(Reference::class, 'reference_id');
    }

    // Get all the student's mobile numbers:
    public function mobileNumbers()
    {
        return $this->hasMany(MobileNumber::class);
    }

    // Get all the student's siblings:
    public function siblings()
    {
        return $this->hasMany(Sibling::class);
    }

    // Get all subjects taken by the student:
    public function subjectsTaken()
    {
        return $this->hasMany(SubjectTaken::class);
    }

    // Get all the sheets taken by the student
    public function sheetsTaken()
    {
        return $this->hasMany(SheetTaken::class);
    }

    // Add the payment relationship
    public function payments()
    {
        return $this->hasOne(Payment::class);
    }

    // Add the payment invoices relationship
    public function paymentInvoices()
    {
        return $this->hasMany(PaymentInvoice::class);
    }

    // Add the payment transactions relationship
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
