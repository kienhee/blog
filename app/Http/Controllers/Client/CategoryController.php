<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;

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
        $category = $this->categoryRepository->getCategoryBySlug($slug);

        if (! $category) {
            abort(404, 'Danh mục không tồn tại');
        }

        // Get all child category IDs (recursive)
        $allChildrenIds = $this->categoryRepository->getAllChildrenIds($category->id);
        $categoryIds = array_merge([$category->id], $allChildrenIds);

        // Get posts from this category and all its children
        $posts = $this->postRepository->gridData()
            ->where('posts.status', 'published')
            ->whereNull('posts.deleted_at')
            ->where(function ($q) {
                $q->whereNull('posts.scheduled_at')
                    ->orWhere('posts.scheduled_at', '<=', now());
            })
            ->whereIn('categories.id', $categoryIds)
            ->orderBy('posts.created_at', 'desc')
            ->paginate(get_posts_per_page());

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
