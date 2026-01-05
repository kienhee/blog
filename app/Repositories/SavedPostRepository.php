<?php

namespace App\Repositories;

use App\Models\SavedPost;
use Illuminate\Support\Facades\Auth;

class SavedPostRepository
{
    protected $model;

    public function __construct(SavedPost $model)
    {
        $this->model = $model;
    }

    /**
     * Kiểm tra xem bài viết đã được lưu chưa
     */
    public function isSaved($userId, $postId): bool
    {
        return $this->model::where('user_id', $userId)
            ->where('post_id', $postId)
            ->exists();
    }

    /**
     * Lấy saved post theo user và post
     */
    public function findByUserAndPost($userId, $postId)
    {
        return $this->model::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();
    }

    /**
     * Lưu bài viết
     */
    public function save($userId, $postId)
    {
        return $this->model::create([
            'user_id' => $userId,
            'post_id' => $postId,
        ]);
    }

    /**
     * Bỏ lưu bài viết
     */
    public function unsave($userId, $postId): bool
    {
        $savedPost = $this->findByUserAndPost($userId, $postId);
        if ($savedPost) {
            return $savedPost->delete();
        }
        return false;
    }

    /**
     * Toggle save/unsave
     */
    public function toggle($userId, $postId): array
    {
        $savedPost = $this->findByUserAndPost($userId, $postId);
        
        if ($savedPost) {
            $savedPost->delete();
            return [
                'saved' => false,
                'message' => 'Đã bỏ lưu bài viết.',
            ];
        } else {
            $this->save($userId, $postId);
            return [
                'saved' => true,
                'message' => 'Đã lưu bài viết thành công.',
            ];
        }
    }
}

