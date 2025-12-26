<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\CategoryRepository;
use App\Repositories\HashTagRepository;
use App\Repositories\PostRepository;
use App\Repositories\PostViewRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppController extends Controller
{
    protected $postRepository;

    protected $postViewRepository;

    protected $categoryRepository;

    protected $hashTagRepository;

    public function __construct(PostRepository $postRepository, PostViewRepository $postViewRepository, CategoryRepository $categoryRepository, HashTagRepository $hashTagRepository)
    {
        $this->postRepository = $postRepository;
        $this->postViewRepository = $postViewRepository;
        $this->categoryRepository = $categoryRepository;
        $this->hashTagRepository = $hashTagRepository;
    }

    public function home()
    {
        // Lấy 6 bài viết mới nhất
        $latestPosts = $this->postRepository->gridData()
            ->where('posts.status', 'published')
            ->whereNull('posts.deleted_at')
            ->where(function ($q) {
                $q->whereNull('posts.scheduled_at')
                    ->orWhere('posts.scheduled_at', '<=', now());
            })
            ->orderBy('posts.created_at', 'desc')
            ->limit(6)
            ->get();

        // Lấy bài viết nổi bật lớn (bài viết đầu tiên có thumbnail, loại trừ 6 bài mới nhất)
        $excludedIds = $latestPosts->pluck('id')->toArray();
        $featuredPost = $this->postRepository->gridData()
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

        // Lấy 4 bài viết gần đây cho tab "Tất cả" (loại trừ featured post và 6 bài mới nhất)
        $allExcludedIds = $excludedIds;
        if ($featuredPost) {
            $allExcludedIds[] = $featuredPost->id;
        }
        $recentPosts = $this->postRepository->gridData()
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

        // Lấy 3 bài viết cho sidebar (loại trừ featured post và recent posts)
        $excludedIds = $recentPosts->pluck('id')->toArray();
        if ($featuredPost) {
            $excludedIds[] = $featuredPost->id;
        }
        
        $sidebarPosts = $this->postRepository->gridData()
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

        // Lấy các danh mục chính (có bài viết, giới hạn 5)
        $categories = $this->categoryRepository->gridData()
            ->having('post_count', '>', 0)
            ->orderBy('post_count', 'desc')
            ->limit(5)
            ->get();

        return view('client.pages.home', compact('latestPosts', 'featuredPost', 'recentPosts', 'sidebarPosts', 'categories'));
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

            $posts = $query->paginate(12)->withQueryString();
        }

        return view('client.pages.search', compact('posts', 'searchQuery'));
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

        return view('client.pages.single', compact('post', 'viewCount', 'readingTime', 'relatedPosts', 'allCategories', 'hashtags', 'allHashtags'));
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
            ->paginate(12);

        return view('client.pages.category', compact('category', 'posts'));
    }

    public function hashtag($slug)
    {
        $hashtag = $this->hashTagRepository->getHashTagBySlug($slug);

        if (! $hashtag) {
            abort(404, 'Hashtag không tồn tại');
        }

        // Get post IDs with this hashtag
        $postIds = DB::table('post_hashtags')
            ->where('hashtag_id', $hashtag->id)
            ->pluck('post_id')
            ->toArray();

        // Get posts with this hashtag
        $posts = $this->postRepository->gridData()
            ->where('posts.status', 'published')
            ->whereNull('posts.deleted_at')
            ->where(function ($q) {
                $q->whereNull('posts.scheduled_at')
                    ->orWhere('posts.scheduled_at', '<=', now());
            })
            ->whereIn('posts.id', $postIds)
            ->orderBy('posts.created_at', 'desc')
            ->paginate(12);

        return view('client.pages.hashtag', compact('hashtag', 'posts'));
    }

    public function contact()
    {
        return view('client.pages.contact');
    }

    public function about()
    {
        return view('client.pages.about');
    }

    /**
     * Xử lý đăng ký newsletter
     */
    public function subscribeNewsletter(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:255',
            ], [
                'email.required' => 'Vui lòng nhập email.',
                'email.email' => 'Email không đúng định dạng.',
                'email.max' => 'Email không được vượt quá 255 ký tự.',
            ]);

            $email = $validated['email'];

            // TODO: Lưu email vào database hoặc gửi đến service newsletter
            // Ví dụ: Newsletter::firstOrCreate(['email' => $email]);
            // Hoặc tích hợp với Mailchimp, SendGrid, etc.

            // Hiện tại chỉ trả về thành công
            // Bạn có thể mở rộng để lưu vào database sau
            return response()->json([
                'success' => true,
                'message' => 'Cảm ơn bạn đã đăng ký nhận tin tức!',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first('email') ?? 'Dữ liệu không hợp lệ.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.',
            ], 500);
        }
    }
}
