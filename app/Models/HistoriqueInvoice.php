<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoriqueInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'old_invoice_id', 'changes'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function oldInvoice()
    {
        return $this->belongsTo(Invoice::class, 'old_invoice_id');
    }
}
