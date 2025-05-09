<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'name',
        'amount',
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
