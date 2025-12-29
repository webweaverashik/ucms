<?php

namespace App\Models\Sheet;

use App\Models\Academic\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SheetTopic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'topic_name',
        'subject_id',
        'status',
        'pdf_path',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function sheetsTaken()
    {
        return $this->hasMany(SheetTopicTaken::class, 'sheet_topic_id');
    }
}
