<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;
use App\Repositories\PostViewRepository;
use Illuminate\Http\Request;

class AppController extends Controller
{
    protected $postRepository;

    protected $postViewRepository;

    protected $categoryRepository;

    public function __construct(PostRepository $postRepository, PostViewRepository $postViewRepository, CategoryRepository $categoryRepository)
    {
        $this->postRepository = $postRepository;
        $this->postViewRepository = $postViewRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function home()
    {
        return view('client.pages.home');
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

        // Filter by category if provided
        if ($request->has('category') && $request->category) {
            $query->where('categories.slug', $request->category);
        }

        // Filter by search term
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('posts.title', 'like', '%'.$search.'%')
                    ->orWhere('posts.description', 'like', '%'.$search.'%')
                    ->orWhere('posts.content', 'like', '%'.$search.'%');
            });
        }

        $posts = $query->paginate(12);

        return view('client.pages.posts', compact('posts'));
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

        // Lấy hashtags nếu chưa có
        $hashtags = [];
        if (isset($post->hashtag_names) && $post->hashtag_names) {
            $hashtagNames = explode(', ', $post->hashtag_names);
            $hashtagSlugs = isset($post->hashtag_slugs) ? explode(', ', $post->hashtag_slugs) : [];
            foreach ($hashtagNames as $index => $name) {
                $hashtags[] = [
                    'name' => trim($name),
                    'slug' => isset($hashtagSlugs[$index]) ? trim($hashtagSlugs[$index]) : null,
                ];
            }
        } elseif ($postModel) {
            $hashtags = $postModel->hashtags->map(function ($tag) {
                return [
                    'name' => $tag->name,
                    'slug' => $tag->slug ?? null,
                ];
            })->toArray();
        }

        return view('client.pages.post', compact('post', 'viewCount', 'readingTime', 'relatedPosts', 'allCategories', 'hashtags'));
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

    public function contact()
    {
        return view('client.pages.contact');
    }
}
