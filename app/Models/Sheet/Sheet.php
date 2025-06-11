<?php

namespace App\Models\Sheet;

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
}
