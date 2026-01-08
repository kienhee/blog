<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;
use App\Support\ClientCacheHelper;

class CategoryController extends Controller
{
    protected $categoryRepository;

    protected $postRepository;

    public function __construct(CategoryRepository $categoryRepository, PostRepository $postRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->postRepository = $postRepository;
    }

    public function category($slug)
    {
        // Lấy category detail (cached - TTL dài hơn)
        $category = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_CATEGORY_DETAIL . 'slug:' . $slug,
            function () use ($slug) {
                return $this->categoryRepository->getCategoryBySlug($slug);
            },
            ClientCacheHelper::TTL_LONG
        );

        if (! $category) {
            abort(404, 'Danh mục không tồn tại');
        }

        // Get all child category IDs (recursive) - cached
        $allChildrenIds = ClientCacheHelper::remember(
            ClientCacheHelper::KEY_CATEGORY_DETAIL . $category->id . ':children',
            function () use ($category) {
                return $this->categoryRepository->getAllChildrenIds($category->id);
            },
            ClientCacheHelper::TTL_LONG
        );
        $categoryIds = array_merge([$category->id], $allChildrenIds);

        // Get posts from this category and all its children (cached per page)
        $page = request()->get('page', 1);
        $cacheKey = ClientCacheHelper::getCategoryPostsKey($category->id, (string) $page);
        
        $posts = ClientCacheHelper::remember(
            $cacheKey,
            function () use ($categoryIds) {
                return $this->postRepository->gridData()
                    ->where('posts.status', 'published')
                    ->whereNull('posts.deleted_at')
                    ->where(function ($q) {
                        $q->whereNull('posts.scheduled_at')
                            ->orWhere('posts.scheduled_at', '<=', now());
                    })
                    ->whereIn('categories.id', $categoryIds)
                    ->orderBy('posts.created_at', 'desc')
                    ->paginate(get_posts_per_page());
            }
        );

        // Pass model for SEO
        $seoModel = $category;

        // Build breadcrumbs with absolute URLs for structured data
        $breadcrumbs = [
            ['name' => 'Trang chủ', 'url' => route('client.home', [], true)],
            ['name' => $category->name, 'url' => route('client.category', ['slug' => $category->slug], true)],
        ];

        return view('client.pages.category', compact('category', 'posts', 'seoModel', 'breadcrumbs'));
    }
}
