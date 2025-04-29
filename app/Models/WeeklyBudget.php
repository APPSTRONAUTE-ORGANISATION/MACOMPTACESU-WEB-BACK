<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'week',
        'amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'week' => 'integer',
        'amount' => 'double',
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }
}
