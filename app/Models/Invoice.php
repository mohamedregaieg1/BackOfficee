<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model {
    use HasFactory;

    protected $fillable = [
        'type',
        'creation_date',
        'number',
        'additional_date_type',
        'additional_date',
        'company_id',
        'client_id'
    ];

    // Relations
    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function client() {
        return $this->belongsTo(Client::class);
    }

    public function services() {
        return $this->hasMany(Service::class);
    }
}

