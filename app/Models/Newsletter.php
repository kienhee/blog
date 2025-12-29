<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Newsletter extends Model
{
    use HasFactory, SoftDeletes;

    public $timestamps = true;

    protected $fillable = [
        'email',
        'status',
        'subscribed_at',
        'unsubscribed_at',
        'scroll_percentage',
        'time_on_page',
        'is_human',
        'spam_score',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'scroll_percentage' => 'decimal:2',
        'time_on_page' => 'integer',
        'is_human' => 'boolean',
        'spam_score' => 'integer',
    ];
}
