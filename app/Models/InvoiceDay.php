<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'work_date',
        'hours',
        'trailers',
    ];

    protected $casts = [
        'work_date' => 'date',
    ];

    public function Invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
