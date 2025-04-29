<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'expense_label_id',
        'amount',
        'expense_file',
    ];

    protected $casts = [
        'amount' => 'double',
    ];

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function ExpenseLabel()
    {
        return $this->belongsTo(ExpenseLabel::class);
    }
}
