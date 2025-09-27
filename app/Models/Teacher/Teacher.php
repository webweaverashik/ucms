<?php
namespace App\Models\Teacher;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Teacher extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'phone', 'email', 'password', 'photo_url', 'base_salary', 'is_active', 'deleted_by'];

    protected $casts = [
        'base_salary' => 'integer', // Ensures salary is always treated as an integer
        'is_active'   => 'boolean',
    ];

    /**
     * Get the user who deleted this teacher (if applicable).
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
