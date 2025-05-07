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
        'client_id',
        'payment_mode',
        'amount_paid',
        'unpaid_amount',
        'due_date',
        'payment_status',
        'total_ttc',
        'total_tva',
        'total_ht'
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
    public function historiqueShipments()
    {
        return $this->hasMany(HistoriqueInvoice::class, 'invoice_id');
    }
    public function relatedInvoices()
{
    return $this->hasMany(Invoice::class, 'original_invoice_id');
}
public function originalInvoice()
{
    return $this->belongsTo(Invoice::class, 'original_invoice_id');
}

}

