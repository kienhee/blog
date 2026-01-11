<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;
use App\Support\ClientCacheHelper;
use Illuminate\Http\Request;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class HomeController extends Controller
{
    protected $postRepository;

    protected $categoryRepository;

    public function __construct(PostRepository $postRepository, CategoryRepository $categoryRepository)
    {
        $this->postRepository = $postRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function home()
    {
        // Lấy 6 bài viết mới nhất (cached)
        $latestPosts = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_HOME_LATEST_POSTS,
            function () {
                return $this->postRepository->gridData()
                    ->where('posts.status', 'published')
                    ->whereNull('posts.deleted_at')
                    ->where(function ($q) {
                        $q->whereNull('posts.scheduled_at')
                            ->orWhere('posts.scheduled_at', '<=', now());
                    })
                    ->orderBy('posts.created_at', 'desc')
                    ->limit(6)
                    ->get();
            }
        );

        // Lấy bài viết nổi bật lớn (cached)
        $excludedIds = $latestPosts->pluck('id')->toArray();
        $featuredPost = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_HOME_FEATURED_POST,
            function () use ($excludedIds) {
                return $this->postRepository->gridData()
                    ->where('posts.status', 'published')
                    ->whereNull('posts.deleted_at')
                    ->whereNotNull('posts.thumbnail')
                    ->where(function ($q) {
                        $q->whereNull('posts.scheduled_at')
                            ->orWhere('posts.scheduled_at', '<=', now());
                    })
                    ->whereNotIn('posts.id', $excludedIds)
                    ->orderBy('posts.created_at', 'desc')
                    ->first();
            }
        );

        // Lấy 4 bài viết gần đây cho tab "Tất cả" (cached)
        $allExcludedIds = $excludedIds;
        if ($featuredPost) {
            $allExcludedIds[] = $featuredPost->id;
        }
        $recentPosts = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_HOME_RECENT_POSTS,
            function () use ($allExcludedIds) {
                return $this->postRepository->gridData()
                    ->where('posts.status', 'published')
                    ->whereNull('posts.deleted_at')
                    ->where(function ($q) {
                        $q->whereNull('posts.scheduled_at')
                            ->orWhere('posts.scheduled_at', '<=', now());
                    })
                    ->whereNotIn('posts.id', $allExcludedIds)
                    ->orderBy('posts.created_at', 'desc')
                    ->limit(4)
                    ->get();
            }
        );

        // Lấy 3 bài viết cho sidebar (cached)
        $excludedIds = $recentPosts->pluck('id')->toArray();
        if ($featuredPost) {
            $excludedIds[] = $featuredPost->id;
        }

        $sidebarPosts = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_HOME_SIDEBAR_POSTS,
            function () use ($excludedIds) {
                return $this->postRepository->gridData()
                    ->where('posts.status', 'published')
                    ->whereNull('posts.deleted_at')
                    ->where(function ($q) {
                        $q->whereNull('posts.scheduled_at')
                            ->orWhere('posts.scheduled_at', '<=', now());
                    })
                    ->when(count($excludedIds) > 0, function ($q) use ($excludedIds) {
                        $q->whereNotIn('posts.id', $excludedIds);
                    })
                    ->orderBy('posts.created_at', 'desc')
                    ->limit(3)
                    ->get();
            }
        );

        // Lấy các danh mục chính (cached - TTL dài hơn vì ít thay đổi)
        $categories = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_HOME_CATEGORIES,
            function () {
                return $this->categoryRepository->gridData()
                    ->having('post_count', '>', 0)
                    ->orderBy('post_count', 'desc')
                    ->limit(5)
                    ->get();
            },
            ClientCacheHelper::TTL_LONG
        );

        // SEO Data for homepage
        $seoModel = new SEOData(
            title: 'Trang chủ',
            description: 'Khám phá các bài viết mới nhất về lập trình, công nghệ và cuộc sống. Chia sẻ kiến thức và kinh nghiệm từ cộng đồng.',
            image: asset_shared_url_v2('images/background-callback.jpg'),
            url: route('client.home', [], false),
            type: 'website',
        );

        return view('client.pages.home', compact('latestPosts', 'featuredPost', 'recentPosts', 'sidebarPosts', 'categories', 'seoModel'));
    }

    public function search(Request $request)
    {
        $searchQuery = $request->get('q', '');
        $posts = collect();

        if (! empty($searchQuery)) {
            $query = $this->postRepository->gridData()
                ->where('posts.status', 'published')
                ->whereNull('posts.deleted_at')
                ->where(function ($q) {
                    $q->whereNull('posts.scheduled_at')
                        ->orWhere('posts.scheduled_at', '<=', now());
                })
                ->where(function ($q) use ($searchQuery) {
                    $q->where('posts.title', 'like', '%'.$searchQuery.'%')
                        ->orWhere('posts.description', 'like', '%'.$searchQuery.'%')
                        ->orWhere('posts.content', 'like', '%'.$searchQuery.'%');
                })
                ->orderBy('posts.created_at', 'desc');

            $posts = $query->paginate(get_posts_per_page())->withQueryString();
        }

        // SEO Data for search page
        $seoModel = new SEOData(
            title: $searchQuery ? "Tìm kiếm: {$searchQuery}" : 'Tìm kiếm',
            description: $searchQuery 
                ? "Kết quả tìm kiếm cho từ khóa: {$searchQuery}. Tìm thấy {$posts->total()} bài viết liên quan."
                : 'Tìm kiếm bài viết trong blog',
            url: route('client.search', [], false) . ($searchQuery ? '?q=' . urlencode($searchQuery) : ''),
            type: 'website',
            robots: 'noindex, follow', // Don't index search results
        );

        return view('client.pages.search', compact('posts', 'searchQuery', 'seoModel'));
    }

    public function posts(Request $request)
    {
        $query = $this->postRepository->gridData()
            ->where('posts.status', 'published')
            ->whereNull('posts.deleted_at')
            ->where(function ($q) {
                $q->whereNull('posts.scheduled_at')
                    ->orWhere('posts.scheduled_at', '<=', now());
            })
            ->orderBy('posts.created_at', 'desc');

        // Filter by search term
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('posts.title', 'like', '%'.$search.'%')
                    ->orWhere('posts.description', 'like', '%'.$search.'%')
                    ->orWhere('posts.content', 'like', '%'.$search.'%');
            });
        }

        $posts = $query->paginate(get_posts_per_page());

        // SEO Data for posts listing page
        $seoModel = new SEOData(
            title: 'Tất cả bài viết',
            description: 'Xem tất cả các bài viết mới nhất về lập trình, công nghệ và cuộc sống. Khám phá nội dung đa dạng từ cộng đồng.',
            url: route('client.posts', [], false),
            type: 'website',
        );

        return view('client.pages.posts', compact('posts', 'seoModel'));
    }
}
