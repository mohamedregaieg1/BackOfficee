<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'tva_number',
        'address',
        'postal_code',
        'country',
        'rib_bank',
        'email',
        'website',
        'phone_number',
        'image_path'
    ];

    

}
