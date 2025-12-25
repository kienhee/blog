<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostHashtag extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = ['post_id', 'hashtag_id'];

    /**
     * Gắn thẻ Hashtag vào bài đăng.
     *
     * @param  array|int[]  $hashtagIds
     * @param  int  $blogId
     */
    public static function attachHashtagsToPost($hashtagIds, $blogId): void
    {
        $data = collect($hashtagIds)->map(fn ($id) => [
            'post_id' => $blogId,
            'hashtag_id' => $id,
        ])->toArray();

        static::insert($data);
    }
    /**
     * Summary of deleteByPostId
     * @param mixed $postId
     * @return bool|null
     */
    public static function deleteByPostId($postId)
    {
        return self::where('post_id', $postId)->delete();
    }
}
