<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\SavedPostRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedPostController extends Controller
{
    protected $savedPostRepository;

    public function __construct(SavedPostRepository $savedPostRepository)
    {
        $this->savedPostRepository = $savedPostRepository;
    }

    /**
     * Save a post
     */
    public function save(Request $request, $postId)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để lưu bài viết.',
            ], 401);
        }

        $userId = Auth::id();

        // Kiểm tra xem đã lưu chưa
        if ($this->savedPostRepository->isSaved($userId, $postId)) {
            return response()->json([
                'success' => false,
                'message' => 'Bài viết đã được lưu trước đó.',
                'saved' => true,
            ]);
        }

        // Lưu bài viết
        $this->savedPostRepository->save($userId, $postId);

        return response()->json([
            'success' => true,
            'message' => 'Đã lưu bài viết thành công.',
            'saved' => true,
        ]);
    }

    /**
     * Unsave a post
     */
    public function unsave(Request $request, $postId)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để bỏ lưu bài viết.',
            ], 401);
        }

        $userId = Auth::id();

        if (!$this->savedPostRepository->isSaved($userId, $postId)) {
            return response()->json([
                'success' => false,
                'message' => 'Bài viết chưa được lưu.',
                'saved' => false,
            ]);
        }

        $this->savedPostRepository->unsave($userId, $postId);

        return response()->json([
            'success' => true,
            'message' => 'Đã bỏ lưu bài viết.',
            'saved' => false,
        ]);
    }

    /**
     * Toggle save/unsave a post
     */
    public function toggle(Request $request, $postId)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng đăng nhập để lưu bài viết.',
            ], 401);
        }

        $userId = Auth::id();
        $result = $this->savedPostRepository->toggle($userId, $postId);

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'saved' => $result['saved'],
        ]);
    }

    /**
     * Check if a post is saved by current user
     */
    public function check(Request $request, $postId)
    {
        if (!Auth::check()) {
            return response()->json([
                'saved' => false,
            ]);
        }

        $userId = Auth::id();
        $saved = $this->savedPostRepository->isSaved($userId, $postId);

        return response()->json([
            'saved' => $saved,
        ]);
    }
}
