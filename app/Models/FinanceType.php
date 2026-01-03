<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceType extends Model
{
    use HasFactory;

    protected $table = 'finance_type';

    protected $fillable = [
        'name',
    ];

    /**
     * Get the finance days for the type.
     */
    public function financeDays()
    {
        return $this->hasMany(FinanceDay::class, 'finance_type_id');
    }
}
