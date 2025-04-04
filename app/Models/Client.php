<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model {
    use HasFactory;

    protected $fillable = [
        'client_type',
        'name',
        'tva_number_client',
        'address',
        'rib_bank',
        'postal_code',
        'country',
        'email',
        'phone_number'
    ];

    public function invoices() {
        return $this->hasMany(Invoice::class);
    }
}