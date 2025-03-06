<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class FixedLeaves extends Model
{
    use HasFactory;

    protected $fillable = ['leave_type', 'max_days'];


    public function leaves()
    {
        return $this->hasMany(Leave::class, 'leave_type', 'leave_type');
    }
}