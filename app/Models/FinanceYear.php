<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanceYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'target',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'target' => 'array',
        ];
    }

    /**
     * Get the user that owns the finance year.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the finance months for the year.
     */
    public function financeMonths()
    {
        return $this->hasMany(FinanceMonth::class, 'year_id');
    }
}
