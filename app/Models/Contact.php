<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Yajra\DataTables\Facades\DataTables;

class Contact extends Model
{
    use HasFactory, SoftDeletes;
    public    $timestamps = true;
    protected $fillable   = [
        'full_name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
    ];

    /**
     * Relationship với ContactReply
     */
    public function replies()
    {
        return $this->hasMany(ContactReply::class)->orderBy('created_at', 'desc');
    }
}
