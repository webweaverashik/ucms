<?php
namespace App\Models\Sheet;

use App\Models\Academic\ClassName;
use App\Models\Academic\Subject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sheet extends Model
{
    use HasFactory;

    protected $fillable = ['class_id', 'price'];

    public function class ()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function sheetPayments()
    {
        return $this->hasMany(SheetPayment::class)->whereHas('invoice.invoiceType', fn($q) => $q->where('type_name', 'sheet_fee'));
    }

    public function sheetPaymentsCount()
    {
        return $this->hasMany(SheetPayment::class);
    }

    public function sheetTopics()
    {
        return $this->hasManyThrough(
            SheetTopic::class,
            Subject::class,
            'class_id',   // Foreign key on Subject table
            'subject_id', // Foreign key on SheetTopic table
            'class_id',   // Local key on Sheet table
            'id',         // Local key on Subject table
        );
    }
}
