<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\Comment\StoreRequest;
use App\Models\Post;
use App\Repositories\CommentRepository;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    protected $commentRepository;

    public function __construct(CommentRepository $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    /**
     * Store a newly created comment.
     */
    public function store(StoreRequest $request)
    {
        try {
            $post = Post::find($request->validated()['post_id']);

            if (!$post) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bài viết không tồn tại.',
                ], 404);
            }

            if (!$post->allow_comment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bình luận đã được tắt cho bài viết này.',
                ], 403);
            }

            $comment = $this->commentRepository->createComment([
                'post_id' => $request->validated()['post_id'],
                'user_id' => Auth::id(),
                'content' => $request->validated()['content'],
                'parent_id' => $request->validated()['parent_id'] ?? null,
            ]);

            $comment->load('user');

            return response()->json([
                'status' => true,
                'message' => 'Bình luận đã được gửi thành công.',
                'data' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user' => [
                        'id' => $comment->user->id,
                        'full_name' => $comment->user->full_name ?? $comment->user->email,
                        'avatar' => $comment->user->avatar ? thumb_path($comment->user->avatar) : null,
                    ],
                    'created_at' => $comment->created_at->diffForHumans(),
                    'created_at_full' => $comment->created_at->format('d/m/Y H:i'),
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi gửi bình luận.',
            ], 500);
        }
    }
}
