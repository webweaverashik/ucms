<?php

namespace App\Models\Sheets;

use App\Models\Academic\Subject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SheetTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_name',
        'subject_id',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function sheetsTaken()
    {
        return $this->hasMany(SheetTaken::class, 'sheet_topic_id');
    }
}
