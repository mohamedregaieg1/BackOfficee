<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'number_of_days',
    ];

    protected $dates = [
        'start_date',
        'end_date',
    ];

    public function leaves()
    {
        return Leave::where(function ($query) {
            $query->whereBetween('start_date', [$this->start_date, $this->end_date])
                  ->orWhereBetween('end_date', [$this->start_date, $this->end_date])
                  ->orWhere(function ($query) {
                      $query->where('start_date', '<=', $this->start_date)
                            ->where('end_date', '>=', $this->end_date);
                  });
        });
    }
}
