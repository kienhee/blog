<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Repositories\HashTagRepository;
use App\Repositories\PostRepository;
use Illuminate\Support\Facades\DB;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class HashtagController extends Controller
{
    protected $hashTagRepository;

    protected $postRepository;

    public function __construct(HashTagRepository $hashTagRepository, PostRepository $postRepository)
    {
        $this->hashTagRepository = $hashTagRepository;
        $this->postRepository = $postRepository;
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

        // SEO Data for hashtag page
        $seoModel = new SEOData(
            title: "Tag: {$hashtag->name}",
            description: "Khám phá tất cả bài viết về {$hashtag->name}. Tìm thấy {$posts->total()} bài viết liên quan đến chủ đề này.",
            url: route('client.hashtag', ['slug' => $hashtag->slug], false),
            type: 'website',
        );

        return view('client.pages.hashtag', compact('hashtag', 'posts', 'seoModel'));
    }
}
