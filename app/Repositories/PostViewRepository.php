<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\PostView;
use Illuminate\Support\Facades\Cache;

class PostViewRepository extends BaseRepository
{
    private $postModel;

    public function __construct(PostView $model, Post $postModel)
    {
        parent::__construct($model);
        $this->postModel = $postModel;
    }

    public function gridData()
    {
        return null;
    }

    public function filterData($grid)
    {
        return null;
    }

    public function renderDataTables($data)
    {
        return null;
    }

    /**
     * Ghi nhận lượt xem bài viết
     *
     * @param  mixed  $post
     */
    public function recordView($post)
    {
        $userId = auth()->id();
        $sessionId = session()->getId();
        $ipAddress = request()->ip();
        // Tạo unique key để check duplicate
        $cacheKey = $this->getCacheKey($post->id, $userId, $sessionId);

        // Nếu đã xem trong 24h thì skip
        if (Cache::has($cacheKey)) {
            return false;
        }

        // Lưu view vào database
        $this->model::create([
            'post_id' => $post->id,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'session_id' => $userId ? null : $sessionId, // Chỉ lưu session nếu guest
            'viewed_at' => now(),
        ]);

        // Cache 24h để tránh duplicate
        Cache::put($cacheKey, true, now()->addHours(24));

        return true;
    }

    /**
     * Tạo cache key unique cho user hoặc guest
     */
    private function getCacheKey($postId, $userId, $sessionId)
    {
        if ($userId) {
            // User đã login: dùng user_id
            return "post_view_{$postId}_user_{$userId}";
        }

        // Guest: dùng session_id
        return "post_view_{$postId}_session_{$sessionId}";
    }

    public function getViewCount($postId)
    {
        return $this->model::where('post_id', $postId)->count();
    }

    /**
     * Lấy danh sách lượt xem của bài viết với pagination và date filter
     */
    public function getPostViews($postId, $perPage = 20, $page = 1, $dateFrom = null, $dateTo = null)
    {
        $query = $this->model::where('post_id', $postId)
            ->with('user:id,full_name,email,avatar');

        // Filter theo ngày với datetime chính xác
        if ($dateFrom) {
            $query->where('viewed_at', '>=', $dateFrom.' 00:00:00');
        }
        if ($dateTo) {
            $query->where('viewed_at', '<=', $dateTo.' 23:59:59');
        }

        $query->orderBy('viewed_at', 'desc');

        $total = $query->count();
        $views = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($view) {
                return [
                    'id' => $view->id,
                    'user_name' => $view->user ? $view->user->full_name : 'Khách',
                    'user_email' => $view->user ? $view->user->email : null,
                    'user_avatar' => $view->user && $view->user->avatar ? $view->user->avatar : null,
                    'ip_address' => $view->ip_address,
                    'viewed_at' => $view->viewed_at->format('d/m/Y H:i:s'),
                    'viewed_at_raw' => $view->viewed_at->toIso8601String(),
                ];
            });

        return [
            'data' => $views,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'has_more' => $page < ceil($total / $perPage),
        ];
    }
}
