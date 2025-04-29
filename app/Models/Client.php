<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Client extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'address',
        'email',
        'phone',
        'notes',
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
