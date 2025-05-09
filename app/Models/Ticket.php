<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'message',
        'user_id',
        'status',
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
