<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseLabel extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_category_id',
        'name',
    ];

    public function ExpenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function Expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
