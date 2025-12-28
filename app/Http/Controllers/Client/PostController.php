<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\CategoryRepository;
use App\Repositories\HashTagRepository;
use App\Repositories\PostRepository;
use App\Repositories\PostViewRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    protected $postRepository;

    protected $postViewRepository;

    protected $categoryRepository;

    protected $hashTagRepository;

    public function __construct(
        PostRepository $postRepository,
        PostViewRepository $postViewRepository,
        CategoryRepository $categoryRepository,
        HashTagRepository $hashTagRepository
    ) {
        $this->postRepository = $postRepository;
        $this->postViewRepository = $postViewRepository;
        $this->categoryRepository = $categoryRepository;
        $this->hashTagRepository = $hashTagRepository;
    }

    public function post($slug)
    {
        $post = $this->postRepository->gridData()
            ->where('posts.slug', $slug)
            ->where('posts.status', 'published')
            ->whereNull('posts.deleted_at')
            ->where(function ($q) {
                $q->whereNull('posts.scheduled_at')
                    ->orWhere('posts.scheduled_at', '<=', now());
            })
            ->first();

        if (! $post) {
            abort(404, 'Bài viết không tồn tại');
        }

        // Ghi nhận lượt xem
        $postModel = $this->postRepository->findById($post->id);
        if ($postModel) {
            $this->postViewRepository->recordView($postModel);
        }

        // Lấy số lượt xem
        $viewCount = $this->postViewRepository->getViewCount($post->id);

        // Tính thời gian đọc (ước tính 200 từ/phút)
        $readingTime = calculateReadingTime($post->content ?? '');

        // Lấy bài viết liên quan (cùng category hoặc hashtags)
        $relatedPosts = $this->getRelatedPosts($post);

        // Lấy categories cho sidebar
        $allCategories = $this->categoryRepository->getCategoryByType();

        // Lấy tất cả hashtags trong hệ thống cho sidebar
        $allHashtags = $this->hashTagRepository->getHashTagByType();

        // Lấy hashtags của bài viết hiện tại từ relationship
        $hashtags = [];
        if ($postModel) {
            $hashtags = $postModel->hashtags->map(function ($tag) {
                return [
                    'name' => $tag->name,
                    'slug' => $tag->slug ?? null,
                ];
            })->toArray();
        }

        // Kiểm tra xem bài viết đã được lưu chưa (nếu user đã đăng nhập)
        $isSaved = false;
        if (Auth::check()) {
            $isSaved = \App\Models\SavedPost::where('user_id', auth()->id())
                ->where('post_id', $post->id)
                ->exists();
        }

        return view('client.pages.single', compact('post', 'viewCount', 'readingTime', 'relatedPosts', 'allCategories', 'hashtags', 'allHashtags', 'isSaved'));
    }

    /**
     * Lấy bài viết liên quan
     */
    private function getRelatedPosts($currentPost)
    {
        $query = $this->postRepository->gridData()
            ->where('posts.status', 'published')
            ->whereNull('posts.deleted_at')
            ->where('posts.id', '!=', $currentPost->id)
            ->where(function ($q) {
                $q->whereNull('posts.scheduled_at')
                    ->orWhere('posts.scheduled_at', '<=', now());
            })
            ->orderBy('posts.created_at', 'desc')
            ->limit(4);

        // Ưu tiên cùng category
        if (isset($currentPost->category_id) && $currentPost->category_id) {
            $query->where('posts.category_id', $currentPost->category_id);
        }

        $relatedPosts = $query->get();

        // Nếu không đủ 4 bài, lấy thêm bài mới nhất
        if ($relatedPosts->count() < 4) {
            $additionalPosts = $this->postRepository->gridData()
                ->where('posts.status', 'published')
                ->whereNull('posts.deleted_at')
                ->where('posts.id', '!=', $currentPost->id)
                ->whereNotIn('posts.id', $relatedPosts->pluck('id')->toArray())
                ->where(function ($q) {
                    $q->whereNull('posts.scheduled_at')
                        ->orWhere('posts.scheduled_at', '<=', now());
                })
                ->orderBy('posts.created_at', 'desc')
                ->limit(4 - $relatedPosts->count())
                ->get();

            $relatedPosts = $relatedPosts->merge($additionalPosts);
        }

        return $relatedPosts->take(4);
    }

    public function getPostsByCategory(Request $request)
    {
        $categoryId = $request->get('category_id');

        $query = $this->postRepository->gridData()
            ->where('posts.status', 'published')
            ->whereNull('posts.deleted_at')
            ->where(function ($q) {
                $q->whereNull('posts.scheduled_at')
                    ->orWhere('posts.scheduled_at', '<=', now());
            });

        if ($categoryId) {
            $category = $this->categoryRepository->findById($categoryId);
            if ($category) {
                // Get all child category IDs (recursive)
                $allChildrenIds = $this->categoryRepository->getAllChildrenIds($category->id);
                $categoryIds = array_merge([$category->id], $allChildrenIds);
                $query->whereIn('categories.id', $categoryIds);
            }
        }

        // Get recent posts (limit 4, không lấy featured post)
        $recentPosts = $query->orderBy('posts.created_at', 'desc')
            ->limit(4)
            ->get();

        // Format recent posts
        $formattedPosts = $recentPosts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'thumbnail' => $post->thumbnail,
                'category_name' => $post->category_name ?? '',
                'meta_description' => $post->meta_description ?? '',
                'content' => $post->content ?? '',
                'created_at' => $post->created_at ? $post->created_at->format('d F Y') : '',
                'created_at_diff' => $post->created_at ? $post->created_at->diffForHumans() : '',
            ];
        });

        return response()->json([
            'success' => true,
            'posts' => $formattedPosts,
        ]);
    }
}
