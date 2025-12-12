<?php
namespace App\Models\Student;

use App\Models\Academic\Batch;
use App\Models\Branch;
use App\Models\Student\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StudentTransferHistory extends Model
{
    protected $table = 'student_transfer_histories';

    protected $fillable = [
        'student_id', 'from_branch_id', 'to_branch_id', 'from_batch_id', 'to_batch_id', 'transferred_by',
    ];
    
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function fromBatch()
    {
        return $this->belongsTo(Batch::class, 'from_batch_id');
    }

    public function toBatch()
    {
        return $this->belongsTo(Batch::class, 'to_batch_id');
    }

    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
