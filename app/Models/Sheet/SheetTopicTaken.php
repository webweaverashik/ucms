<?php

namespace App\Models\Sheet;

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
    ];

    public function sheetTopic()
    {
        return $this->belongsTo(SheetTopic::class, 'sheet_topic_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
