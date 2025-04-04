<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model {
    use HasFactory;

    protected $fillable = [
        'name',
        'quantity',
        'unit',
        'price_ht',
        'tva',
        'total_ht',
        'total_ttc',
        'comment',
        'invoice_id'
    ];

    public function invoice() {
        return $this->belongsTo(Invoice::class);
    }
}