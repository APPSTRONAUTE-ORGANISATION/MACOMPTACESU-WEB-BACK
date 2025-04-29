<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'double',
    ];

    public function Invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
