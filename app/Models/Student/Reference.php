<?php

namespace App\Models\Student;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reference extends Model
{
    use HasFactory;

    protected $fillable = ['referer_id', 'referer_type'];

    
    // Get the referer model (student or teacher).
    public function referer()
    {
        return $this->morphTo();
    }
}
