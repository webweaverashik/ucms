<?php

namespace App\Models\Academic;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassName extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'class_numeral'];

    // Get all subjects associated with this class
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'class_id');
    }
}
