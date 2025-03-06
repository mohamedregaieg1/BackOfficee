<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeavesBalance extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'leave_day_limit', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'user_id'); 
    }
}
