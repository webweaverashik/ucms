<?php
namespace App\Models\Sheet;

use App\Models\Academic\ClassName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
