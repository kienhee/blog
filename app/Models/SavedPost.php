<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedPost extends Model
{
    use HasFactory;

    protected $table = 'saved_posts';

    protected $fillable = [
        'user_id',
        'post_id',
    ];

    /**
     * Get the user that saved the post.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the post that was saved.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
