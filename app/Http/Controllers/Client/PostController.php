<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\CategoryRepository;
use App\Repositories\HashTagRepository;
use App\Repositories\PostRepository;
use App\Repositories\PostViewRepository;
use App\Repositories\SavedPostRepository;
use App\Support\ClientCacheHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    protected $postRepository;
    protected $postViewRepository;
    protected $categoryRepository;
    protected $hashTagRepository;
    protected $savedPostRepository;

    public function __construct(
        PostRepository $postRepository,
        PostViewRepository $postViewRepository,
        CategoryRepository $categoryRepository,
        HashTagRepository $hashTagRepository,
        SavedPostRepository $savedPostRepository
    ) {
        $this->postRepository = $postRepository;
        $this->postViewRepository = $postViewRepository;
        $this->categoryRepository = $categoryRepository;
        $this->hashTagRepository = $hashTagRepository;
        $this->savedPostRepository = $savedPostRepository;
    }

    public function post($slug)
    {
        // Lấy post detail (cached)
        $post = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_POST_DETAIL . 'slug:' . $slug,
            function () use ($slug) {
                return $this->postRepository->getPostBySlug($slug);
            }
        );

        if (! $post) {
            abort(404, 'Bài viết không tồn tại');
        }

        // Ghi nhận lượt xem (không cache vì cần real-time)
        $postModel = $this->postRepository->findById($post->id);
        if ($postModel) {
            $this->postViewRepository->recordView($postModel);
        }

        // Lấy số lượt xem (không cache vì cần real-time)
        $viewCount = $this->postViewRepository->getViewCount($post->id);

        // Tính thời gian đọc
        $readingTime = calculateReadingTime($post->content ?? '');

        // Lấy bài viết trước và sau (cached)
        $prevPost = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_POST_PREV . $post->id,
            function () use ($post) {
                return $this->postRepository->getPrevPost($post);
            }
        );

        $nextPost = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_POST_NEXT . $post->id,
            function () use ($post) {
                return $this->postRepository->getNextPost($post);
            }
        );

        // Lấy bài viết liên quan (cached)
        $relatedPosts = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_POST_RELATED . $post->id,
            function () use ($post) {
                return $this->postRepository->getRelatedPosts($post);
            }
        );

        // Lấy categories và hashtags cho sidebar (cached - TTL dài hơn)
        $allCategories = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_POST_CATEGORIES,
            function () {
                return $this->categoryRepository->getCategoryByType();
            },
            ClientCacheHelper::TTL_LONG
        );

        $allHashtags = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_POST_HASHTAGS,
            function () {
                return $this->hashTagRepository->getHashTagByType();
            },
            ClientCacheHelper::TTL_LONG
        );

        // Lấy hashtags của bài viết hiện tại
        $hashtags = [];
        if ($postModel) {
            $hashtags = $postModel->hashtags->map(function ($tag) {
                return [
                    'name' => $tag->name,
                    'slug' => $tag->slug ?? null,
                ];
            })->toArray();
        }

        // Kiểm tra xem bài viết đã được lưu chưa
        $isSaved = false;
        if (Auth::check() && $postModel) {
            $isSaved = $this->savedPostRepository->isSaved(auth()->id(), $postModel->id);
        }

        // Load comments
        $comments = collect();
        $commentsCount = 0;
        if ($postModel && $postModel->allow_comment) {
            $comments = $postModel->comments()
                ->whereNull('parent_id')
                ->with(['user', 'replies.user'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            $commentsCount = $postModel->comments()->count();
        }

        $post->comments = $comments;
        $post->comments_count = $commentsCount;

        // Pass model for SEO
        $seoModel = $postModel;

        // Build breadcrumbs with absolute URLs for structured data
        $breadcrumbs = [
            ['name' => 'Trang chủ', 'url' => route('client.home', [], true)],
        ];
        
        if ($postModel && $postModel->category) {
            $breadcrumbs[] = [
                'name' => $postModel->category->name,
                'url' => route('client.category', ['slug' => $postModel->category->slug], true)
            ];
        }
        
        $breadcrumbs[] = [
            'name' => $post->title,
            'url' => route('client.post', ['slug' => $post->slug], true)
        ];

        return view('client.pages.single', compact(
            'post',
            'postModel',
            'seoModel',
            'viewCount',
            'readingTime',
            'prevPost',
            'nextPost',
            'relatedPosts',
            'allCategories',
            'hashtags',
            'allHashtags',
            'isSaved',
            'breadcrumbs'
        ));
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
                $allChildrenIds = $this->categoryRepository->getAllChildrenIds($category->id);
                $categoryIds = array_merge([$category->id], $allChildrenIds);
                $query->whereIn('categories.id', $categoryIds);
            }
        }

        $recentPosts = $query->orderBy('posts.created_at', 'desc')
            ->limit(4)
            ->get();

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
