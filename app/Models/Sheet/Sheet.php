<?php
namespace App\Models\Sheet;

use App\Models\Academic\Subject;
use App\Models\Sheet\SheetTopic;
use App\Models\Academic\ClassName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'price',
    ];

    public function class ()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function sheetPayments()
    {
        return $this->hasMany(SheetPayment::class)
            ->whereHas('invoice', function ($query) {
                $query->where('invoice_type', 'sheet_fee');
            });
    }

    public function sheetTopics()
    {
        return $this->hasManyThrough(
            SheetTopic::class,
            Subject::class,
            'class_id',   // Foreign key on Subject table
            'subject_id', // Foreign key on SheetTopic table
            'class_id',   // Local key on Sheet table
            'id'          // Local key on Subject table
        );
    }

}
