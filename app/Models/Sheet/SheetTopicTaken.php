<?php
namespace App\Models\Sheet;

use App\Models\User;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SheetTopicTaken extends Model
{
    use HasFactory;

    protected $table = 'sheet_topics_taken';

    protected $fillable = [
        'sheet_topic_id',
        'student_id',
        'distributed_by',
    ];

    public function sheetTopic()
    {
        return $this->belongsTo(SheetTopic::class, 'sheet_topic_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function getClassAttribute()
    {
        return $this->sheetTopic->subject->class ?? null;
    }

    public function distributedBy()
    {
        return $this->belongsTo(User::class, 'distributed_by')->withTrashed();
    }
}
