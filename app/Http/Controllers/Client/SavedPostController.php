<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\SavedPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedPostController extends Controller
{
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
        $savedPost = SavedPost::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();

        if ($savedPost) {
            return response()->json([
                'success' => false,
                'message' => 'Bài viết đã được lưu trước đó.',
                'saved' => true,
            ]);
        }

        // Lưu bài viết
        SavedPost::create([
            'user_id' => $userId,
            'post_id' => $postId,
        ]);

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

        $savedPost = SavedPost::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();

        if (!$savedPost) {
            return response()->json([
                'success' => false,
                'message' => 'Bài viết chưa được lưu.',
                'saved' => false,
            ]);
        }

        $savedPost->delete();

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

        $savedPost = SavedPost::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();

        if ($savedPost) {
            $savedPost->delete();
            return response()->json([
                'success' => true,
                'message' => 'Đã bỏ lưu bài viết.',
                'saved' => false,
            ]);
        } else {
            SavedPost::create([
                'user_id' => $userId,
                'post_id' => $postId,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Đã lưu bài viết thành công.',
                'saved' => true,
            ]);
        }
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

        $saved = SavedPost::where('user_id', $userId)
            ->where('post_id', $postId)
            ->exists();

        return response()->json([
            'saved' => $saved,
        ]);
    }
}
