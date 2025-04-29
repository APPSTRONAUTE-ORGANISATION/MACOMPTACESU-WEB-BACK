<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'activity_id',
        'total',
        'due_date',
        'invoice_date',
        'invoice_file',
    ];

    protected $casts = [
        'hours' => 'double',
        'trailers' => 'double',
        'total' => 'double',
        'due_date' => 'date',
        'invoice_date' => 'date',
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Client()
    {
        return $this->belongsTo(Client::class);
    }

    public function Activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function InvoiceDays()
    {
        return $this->hasMany(InvoiceDay::class);
    }

    public function Payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function PayableAmount()
    {
        return $this->total - $this->Payments()->sum('amount');
    }
}
