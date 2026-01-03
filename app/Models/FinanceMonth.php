<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceMonth extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year_id',
        'month',
        'total_money',
        'fix_money',
        'invest_money',
        'remaining_money',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'note' => 'array',
        ];
    }

    /**
     * Get the user that owns the finance month.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the finance year that owns the month.
     */
    public function financeYear()
    {
        return $this->belongsTo(FinanceYear::class, 'year_id');
    }

    /**
     * Get the finance days for the month.
     */
    public function financeDays()
    {
        return $this->hasMany(FinanceDay::class, 'month_id');
    }
}
