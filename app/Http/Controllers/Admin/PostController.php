<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Post\StoreRequest;
use App\Http\Requests\Admin\Post\UpdateRequest;
use App\Models\Post;
use App\Repositories\CategoryRepository;
use App\Repositories\HashTagRepository;
use App\Repositories\PostRepository;
use App\Repositories\PostViewRepository;
use Illuminate\Http\Request;

class PostController extends Controller
{
    protected $postRepository;

    protected $categoryRepository;

    protected $hashtagRepository;

    protected $postViewRepository;

    public function __construct(PostRepository $post, CategoryRepository $category, HashTagRepository $hashtag, PostViewRepository $postView)
    {
        $this->postRepository = $post;
        $this->categoryRepository = $category;
        $this->hashtagRepository = $hashtag;
        $this->postViewRepository = $postView;
    }

    public function list()
    {
        $statusLabels = $this->postRepository->getStatusLabel();
        $categoriesData = $this->categoryRepository->getCategoryByType('post');
        $categories = $this->categoryRepository->buildTree($categoriesData->toArray());

        return view('admin.modules.post.list', compact('statusLabels', 'categories'));
    }

    public function ajaxGetData()
    {
        $grid = $this->postRepository->gridData();
        $data = $this->postRepository->filterData($grid);

        return $this->postRepository->renderDataTables($data);
    }

    public function ajaxGetTrashedData()
    {
        $grid = $this->postRepository->gridTrashedData();
        $data = $this->postRepository->filterData($grid);

        return $this->postRepository->renderTrashedDataTables($data);
    }

    public function create()
    {
        $hashtags = $this->hashtagRepository->getHashTagByType('post');
        $statusLabels = $this->postRepository->getStatusLabel();
        $categoriesData = $this->categoryRepository->getCategoryByType('post');
        $categories = $this->categoryRepository->buildTree($categoriesData->toArray());

        return view('admin.modules.post.create', compact('hashtags', 'statusLabels', 'categories'));
    }

    public function store(StoreRequest $request)
    {
        try {
            $data = [
                'title' => $request->input('title'),
                'slug' => $request->input('slug'),
                'status' => $request->input('status'),
                'category_id' => $request->input('category_id'),
                'content' => $request->input('content'),
                'thumbnail' => $request->input('thumbnail'),
                'allow_comment' => $request->boolean('allow_comment'),
                'description' => $request->input('description'),
                'user_id' => auth()->id(),
            ];

            // Xử lý scheduled_at và status
            if ($request->filled('scheduled_at')) {
                $data['scheduled_at'] = $request->input('scheduled_at');
                // Nếu có scheduled_at, đảm bảo status là "scheduled"
                if ($data['status'] !== Post::STATUS_SCHEDULED) {
                    $data['status'] = Post::STATUS_SCHEDULED;
                }
            } else {
                // Nếu không có scheduled_at nhưng status là "scheduled", đổi về "draft"
                if ($data['status'] === Post::STATUS_SCHEDULED) {
                    $data['status'] = Post::STATUS_DRAFT;
                }
                $data['scheduled_at'] = null;
            }

            // Nếu status là "published", không được có scheduled_at
            if ($data['status'] === Post::STATUS_PUBLISHED) {
                $data['scheduled_at'] = null;
            }

            $this->postRepository->createWithHashtags($data, $request->input('hashtags'));

            return back()->with('success', 'Thêm mới thành công');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra');
        }
    }

    public function edit($id)
    {
        $grid = $this->postRepository->gridData();
        $data = $this->postRepository->getPostById($id);
        $post = Post::find($id); // Lấy model để check scheduled_at

        $hashtags = $this->hashtagRepository->getHashTagByType('post');
        $statusLabels = $this->postRepository->getStatusLabel();
        $categoriesData = $this->categoryRepository->getCategoryByType('post');
        $categories = $this->categoryRepository->buildTree($categoriesData->toArray());

        // Kiểm tra xem có thể chỉnh sửa scheduled_at không
        // Chỉ cho phép edit nếu status là draft hoặc scheduled (chưa đăng)
        $canEditScheduledAt = $post &&
                              ($post->status === Post::STATUS_DRAFT || $post->status === Post::STATUS_SCHEDULED) &&
                              (! $post->scheduled_at || $post->scheduled_at > now());

        return view('admin.modules.post.edit', data: compact('data', 'hashtags', 'statusLabels', 'categories', 'post', 'canEditScheduledAt'));
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            $existingPost = $this->postRepository->findById($id);
            if (! $existingPost) {
                return back()->with('error', 'Bài viết không tồn tại');
            }

            $data = [
                'title' => $request->input('title'),
                'slug' => $request->input('slug'),
                'status' => $request->input('status'),
                'category_id' => $request->input('category_id'),
                'content' => $request->input('content'),
                'thumbnail' => $request->input('thumbnail'),
                'allow_comment' => $request->boolean('allow_comment'),
                'description' => $request->input('description'),
                'user_id' => auth()->id(),
            ];

            // Xử lý scheduled_at và status
            $postModel = Post::find($id);
            if ($postModel) {
                $isPublished = $postModel->status === Post::STATUS_PUBLISHED;
                $scheduledTimePassed = $postModel->scheduled_at && $postModel->scheduled_at <= now();
                $canEditScheduled = ! $isPublished && ! $scheduledTimePassed;

                if ($canEditScheduled) {
                    // Chỉ cho phép update scheduled_at nếu bài viết chưa được đăng
                    if ($request->filled('scheduled_at')) {
                        $data['scheduled_at'] = $request->input('scheduled_at');
                        // Nếu có scheduled_at, đảm bảo status là "scheduled"
                        if ($data['status'] !== Post::STATUS_SCHEDULED) {
                            $data['status'] = Post::STATUS_SCHEDULED;
                        }
                    } else {
                        // Nếu không có scheduled_at, xóa scheduled_at cũ
                        $data['scheduled_at'] = null;
                        // Nếu status là "scheduled" nhưng không có scheduled_at, đổi về "draft"
                        if ($data['status'] === Post::STATUS_SCHEDULED) {
                            $data['status'] = Post::STATUS_DRAFT;
                        }
                    }
                } else {
                    // Nếu không thể edit, giữ nguyên scheduled_at hiện tại (không update)
                    unset($data['scheduled_at']);
                }

                // Nếu status là "published", không được có scheduled_at
                if ($data['status'] === Post::STATUS_PUBLISHED) {
                    $data['scheduled_at'] = null;
                }
            }

            $post = $this->postRepository->updateWithHashtags($id, $data, $request->input('hashtags'));

            if (! $post) {
                return back()->with('error', 'Bài viết không tồn tại');
            }

            return back()->with('success', 'Cập nhật thành công');
        } catch (\Throwable $e) {
            return back()->with('error', 'Có lỗi xảy ra');
        }
    }

    public function destroy($id)
    {
        try {
            $post = $this->postRepository->findById($id);
            if (! $post) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bài viết không tồn tại',
                ], 404);
            }

            // Xóa post (sử dụng SoftDeletes)
            $this->postRepository->delete($id);

            return response()->json([
                'status' => true,
                'message' => 'Xóa bài viết thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa bài viết',
            ], 500);
        }
    }

    public function restore($id)
    {
        try {
            $post = \App\Models\Post::withTrashed()->find($id);
            if (! $post || ! $post->trashed()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bài viết không tồn tại trong thùng rác',
                ], 404);
            }

            $this->postRepository->restore($id);

            return response()->json([
                'status' => true,
                'message' => 'Khôi phục bài viết thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi khôi phục bài viết',
            ], 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $post = \App\Models\Post::withTrashed()->find($id);
            if (! $post || ! $post->trashed()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bài viết không tồn tại trong thùng rác',
                ], 404);
            }

            $this->postRepository->forceDelete($id);

            return response()->json([
                'status' => true,
                'message' => 'Xóa vĩnh viễn bài viết thành công',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn bài viết',
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || ! is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một bài viết',
                ], 400);
            }

            $count = $this->postRepository->bulkDelete($ids);

            return response()->json([
                'status' => true,
                'message' => "Đã xóa {$count} bài viết thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa',
            ], 500);
        }
    }

    public function bulkRestore(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || ! is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một bài viết',
                ], 400);
            }

            $count = $this->postRepository->bulkRestore($ids);

            return response()->json([
                'status' => true,
                'message' => "Đã khôi phục {$count} bài viết thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi khôi phục',
            ], 500);
        }
    }

    public function bulkForceDelete(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (empty($ids) || ! is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một bài viết',
                ], 400);
            }

            $count = $this->postRepository->bulkForceDelete($ids);

            return response()->json([
                'status' => true,
                'message' => "Đã xóa vĩnh viễn {$count} bài viết thành công",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi xóa vĩnh viễn',
            ], 500);
        }
    }

    /**
     * Bulk move posts to another category
     */
    public function bulkMoveCategory(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            $categoryId = $request->input('category_id');

            if (empty($ids) || ! is_array($ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn ít nhất một bài viết',
                ], 400);
            }

            if (! $categoryId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vui lòng chọn danh mục đích',
                ], 400);
            }

            $category = \App\Models\Category::find($categoryId);
            if (! $category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Danh mục đích không tồn tại',
                ], 404);
            }

            $count = $this->postRepository->bulkMoveCategory($ids, $categoryId);

            return response()->json([
                'status' => true,
                'message' => "Đã chuyển {$count} bài viết sang danh mục '{$category->name}'",
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Có lỗi xảy ra khi chuyển danh mục',
            ], 500);
        }
    }

    /**
     * Lấy danh sách lượt xem của bài viết
     */
    public function getPostViews($id, Request $request)
    {
        $post = $this->postRepository->findById($id);
        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Bài viết không tồn tại',
            ], 404);
        }

        $page = (int) $request->input('page', 1);
        $perPage = 20;
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $result = $this->postViewRepository->getPostViews($id, $perPage, $page, $dateFrom, $dateTo);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => [
                'total' => $result['total'],
                'per_page' => $result['per_page'],
                'current_page' => $result['current_page'],
                'last_page' => $result['last_page'],
                'has_more' => $result['has_more'],
            ],
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'view_count' => $result['total'],
                'created_at' => $post->created_at->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Hiển thị bài viết đã publish (xem trước)
     */
    public function publish($id)
    {
        $post = Post::with(['user', 'hashtags', 'category'])->find($id);
        
        if (! $post) {
            abort(404, 'Bài viết không tồn tại');
        }

        // Ghi nhận lượt xem khi vào trang
        $this->postViewRepository->recordView($post);

        // Lấy số lượt xem (sau khi đã ghi nhận lượt xem mới)
        $viewCount = $this->postViewRepository->getViewCount($id);

        return view('admin.modules.post.publish', compact('post', 'viewCount'));
    }
}
