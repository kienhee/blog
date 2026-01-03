<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'finance_type_id',
        'money',
        'month_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the finance day.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the finance month that owns the day.
     */
    public function financeMonth()
    {
        return $this->belongsTo(FinanceMonth::class, 'month_id');
    }

    /**
     * Get the finance type that owns the day.
     */
    public function financeType()
    {
        return $this->belongsTo(FinanceType::class, 'finance_type_id');
    }
}
