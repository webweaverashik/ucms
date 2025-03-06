<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'branch_name',
        'address',
        'phone_number',
    ];

    /**
     * Get all the users in the branch
     */
    public function users() {
        return $this->hasMany(User::class);
    }
}
