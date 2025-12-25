<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'user_id',
        'subject',
        'message',
    ];

    /**
     * Relationship với Contact
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Relationship với User (người trả lời)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
