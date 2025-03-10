<?php

namespace App\Models\Sheets;

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

    public function class()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function payments()
    {
        return $this->hasMany(SheetPayment::class, 'sheet_id');
    }
}
