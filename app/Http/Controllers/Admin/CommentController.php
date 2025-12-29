<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Comment\ReplyRequest;
use App\Models\Comment;
use App\Repositories\CommentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    protected $commentRepository;

    public function __construct(CommentRepository $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    /**
     * Display list of comments
     */
    public function list()
    {
        $statusLabels = $this->commentRepository->getStatusLabel();
        return view('admin.modules.comment.list', compact('statusLabels'));
    }

    /**
     * Get comments data for DataTables
     */
    public function ajaxGetData(Request $request)
    {
        $grid = $this->commentRepository->gridData();
        $data = $this->commentRepository->filterData($grid);
        return $this->commentRepository->renderDataTables($data);
    }

    /**
     * Count pending comments
     */
    public function countPending()
    {
        $count = $this->commentRepository->countPending();
        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Show comment details
     */
    public function show($id)
    {
        $comment = $this->commentRepository->findById($id);

        if (!$comment) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bình luận không tồn tại.',
                ], 404);
            }
            abort(404, 'Bình luận không tồn tại.');
        }

        // Load relationships
        $comment->load(['user', 'post', 'parent', 'replies.user']);

        // If AJAX request, return JSON (for modal)
        if (request()->ajax() || request()->wantsJson()) {
            $replies = $comment->replies->map(function ($reply) {
                return [
                    'id' => $reply->id,
                    'content' => $reply->content,
                    'user_name' => $reply->user ? ($reply->user->full_name ?? $reply->user->email) : 'Hệ thống',
                    'user_avatar' => $reply->user && $reply->user->avatar ? thumb_path($reply->user->avatar) : null,
                    'created_at' => $reply->created_at->format('d/m/Y H:i'),
                    'created_at_human' => $reply->created_at->diffForHumans(),
                ];
            });

            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $comment->id,
                    'user' => $comment->user ? [
                        'id' => $comment->user->id,
                        'full_name' => $comment->user->full_name ?? $comment->user->email,
                        'email' => $comment->user->email,
                        'avatar' => $comment->user->avatar ? thumb_path($comment->user->avatar) : null,
                    ] : null,
                    'post' => $comment->post ? [
                        'id' => $comment->post->id,
                        'title' => $comment->post->title,
                        'slug' => $comment->post->slug,
                    ] : null,
                    'content' => $comment->content,
                    'status' => $comment->status,
                    'parent' => $comment->parent ? [
                        'id' => $comment->parent->id,
                        'content' => $comment->parent->content,
                        'user_name' => $comment->parent->user ? ($comment->parent->user->full_name ?? $comment->parent->user->email) : 'Hệ thống',
                    ] : null,
                    'created_at' => $comment->created_at->format('d/m/Y H:i'),
                    'created_at_human' => $comment->created_at->diffForHumans(),
                    'replies' => $replies,
                ],
            ]);
        }

        // If regular request, return view
        $statusLabels = $this->commentRepository->getStatusLabel();
        return view('admin.modules.comment.show', compact('comment', 'statusLabels'));
    }

    /**
     * Reply to a comment
     */
    public function reply(ReplyRequest $request, $id)
    {
        try {
            $comment = $this->commentRepository->findById($id);

            if (!$comment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bình luận không tồn tại.',
                ], 404);
            }

            DB::beginTransaction();

            // Create reply comment
            $reply = Comment::create([
                'post_id' => $comment->post_id,
                'user_id' => Auth::id(),
                'content' => $request->input('content'),
                'status' => Comment::STATUS_APPROVED, // Admin replies are auto-approved
                'parent_id' => $comment->id,
            ]);

            // Load user relationship
            $reply->load('user');

            DB::commit();

            // Load comment with replies to return
            $comment->load(['replies.user']);
            $replies = $comment->replies->map(function ($reply) {
                return [
                    'id' => $reply->id,
                    'content' => $reply->content,
                    'user_name' => $reply->user ? ($reply->user->full_name ?? $reply->user->email) : 'Hệ thống',
                    'user_avatar' => $reply->user && $reply->user->avatar ? thumb_path($reply->user->avatar) : null,
                    'created_at' => $reply->created_at->format('d/m/Y H:i'),
                    'created_at_human' => $reply->created_at->diffForHumans(),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Trả lời thành công',
                'data' => [
                    'reply' => [
                        'id' => $reply->id,
                        'content' => $reply->content,
                        'user_name' => Auth::user() ? (Auth::user()->full_name ?? Auth::user()->email) : 'Hệ thống',
                        'user_avatar' => Auth::user() && Auth::user()->avatar ? thumb_path(Auth::user()->avatar) : null,
                        'created_at' => $reply->created_at->format('d/m/Y H:i'),
                        'created_at_human' => $reply->created_at->diffForHumans(),
                    ],
                    'replies' => $replies,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change comment status
     */
    public function changeStatus($id, $status)
    {
        $comment = $this->commentRepository->findById($id);

        if (!$comment) {
            return response()->json([
                'status' => false,
                'message' => 'Bình luận không tồn tại.',
            ], 404);
        }

        // Validate status
        $validStatuses = [
            Comment::STATUS_PENDING,
            Comment::STATUS_APPROVED,
            Comment::STATUS_SPAM,
            Comment::STATUS_TRASH,
        ];

        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'status' => false,
                'message' => 'Trạng thái không hợp lệ.',
            ], 400);
        }

        // If changing to trash, soft delete the comment
        if ($status === Comment::STATUS_TRASH) {
            $comment->delete(); // Soft delete
        } else {
            // If restoring from trash, ensure it's restored first
            if ($comment->trashed()) {
                $comment->restore();
            }
            $comment->status = $status;
            $comment->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật trạng thái thành công',
        ]);
    }

    /**
     * Delete comment (soft delete)
     */
    public function destroy($id)
    {
        $comment = $this->commentRepository->findById($id);

        if (!$comment) {
            return response()->json([
                'status' => false,
                'message' => 'Bình luận không tồn tại.',
            ], 404);
        }

        $comment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa bình luận thành công',
        ]);
    }

    /**
     * Get trashed comments data for DataTables
     */
    public function ajaxGetTrashedData(Request $request)
    {
        $grid = $this->commentRepository->gridTrashedData();
        $data = $this->commentRepository->filterTrashedData($grid);
        return $this->commentRepository->renderTrashedDataTables($data);
    }

    /**
     * Restore a trashed comment
     */
    public function restore($id)
    {
        $result = $this->commentRepository->restore($id);

        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể khôi phục bình luận.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Khôi phục bình luận thành công',
        ]);
    }

    /**
     * Force delete a comment
     */
    public function forceDelete($id)
    {
        $result = $this->commentRepository->forceDelete($id);

        if (!$result) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể xóa vĩnh viễn bình luận.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Xóa vĩnh viễn bình luận thành công',
        ]);
    }

    /**
     * Bulk restore comments
     */
    public function bulkRestore(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'Vui lòng chọn ít nhất một bình luận.',
            ], 400);
        }

        $count = $this->commentRepository->bulkRestore($ids);

        return response()->json([
            'status' => true,
            'message' => "Đã khôi phục {$count} bình luận thành công",
            'count' => $count,
        ]);
    }

    /**
     * Bulk force delete comments
     */
    public function bulkForceDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'Vui lòng chọn ít nhất một bình luận.',
            ], 400);
        }

        $count = $this->commentRepository->bulkForceDelete($ids);

        return response()->json([
            'status' => true,
            'message' => "Đã xóa vĩnh viễn {$count} bình luận thành công",
            'count' => $count,
        ]);
    }

    /**
     * Bulk delete comments (soft delete)
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'Vui lòng chọn ít nhất một bình luận.',
            ], 400);
        }

        $count = $this->commentRepository->bulkDelete($ids);

        return response()->json([
            'status' => true,
            'message' => "Đã xóa {$count} bình luận thành công",
            'count' => $count,
        ]);
    }

    /**
     * Bulk change status
     */
    public function bulkChangeStatus(Request $request)
    {
        $ids = $request->input('ids', []);
        $status = $request->input('status');

        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'status' => false,
                'message' => 'Vui lòng chọn ít nhất một bình luận.',
            ], 400);
        }

        // Validate status
        $validStatuses = [
            Comment::STATUS_PENDING,
            Comment::STATUS_APPROVED,
            Comment::STATUS_SPAM,
            Comment::STATUS_TRASH,
        ];

        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'status' => false,
                'message' => 'Trạng thái không hợp lệ.',
            ], 400);
        }

        $count = $this->commentRepository->bulkChangeStatus($ids, $status);

        $statusLabels = $this->commentRepository->getStatusLabel();
        $statusLabel = $statusLabels[$status] ?? $status;

        return response()->json([
            'status' => true,
            'message' => "Đã cập nhật {$count} bình luận thành {$statusLabel}",
            'count' => $count,
        ]);
    }
}

