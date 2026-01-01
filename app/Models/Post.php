<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class Post extends Model
{
    use HasFactory, SoftDeletes, HasSEO;

    public $timestamps = true;

    public function hashtags()
    {
        return $this->belongsToMany(HashTag::class, 'post_hashtags', 'post_id', 'hashtag_id');
    }

    public function post_hashtags()
    {
        return $this->hasMany(PostHashtag::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class)->where('status', 'approved');
    }

    public function allComments()
    {
        return $this->hasMany(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function savedPosts()
    {
        return $this->hasMany(SavedPost::class);
    }

    protected $fillable = [
        'thumbnail',
        'title',
        'slug',
        'content',
        'status',
        'description',
        'category_id',
        'allow_comment',
        'user_id',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    const STATUS_DRAFT = 'draft';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_PUBLISHED = 'published';

    /**
     * Get dynamic SEO data for the post
     */
    public function getDynamicSEOData(): SEOData
    {
        // Load relationships if not already loaded
        if (!$this->relationLoaded('user')) {
            $this->load('user');
        }
        if (!$this->relationLoaded('category')) {
            $this->load('category');
        }

        // Generate description from content if not available
        $description = $this->description;
        if (!$description && $this->content) {
            $description = strip_tags($this->content);
            $description = preg_replace('/\s+/', ' ', $description); // Remove extra whitespace
            $description = trim($description);
            $description = mb_substr($description, 0, 160);
            if (mb_strlen($description) >= 160) {
                $description .= '...';
            }
        }

        // Build full absolute URL for image (required for Open Graph)
        $imageUrl = null;
        if ($this->thumbnail) {
            $imageUrl = $this->thumbnail;
            if (!str_starts_with($imageUrl, 'http')) {
                // Ensure path starts with /
                $path = '/' . ltrim($imageUrl, '/');
                // Encode each path segment properly (keep slashes)
                $pathSegments = explode('/', trim($path, '/'));
                $encodedSegments = array_map(function($segment) {
                    return $segment ? rawurlencode($segment) : '';
                }, $pathSegments);
                $encodedPath = '/' . implode('/', array_filter($encodedSegments));
                // Build full absolute URL
                $baseUrl = rtrim(config('app.url'), '/');
                $imageUrl = $baseUrl . $encodedPath;
            }
        }

        // Build canonical URL
        $canonicalUrl = route('client.post', ['slug' => $this->slug], false);
        if (!str_starts_with($canonicalUrl, 'http')) {
            $canonicalUrl = url($canonicalUrl);
        }

        // Load hashtags if not loaded
        if (!$this->relationLoaded('hashtags')) {
            $this->load('hashtags');
        }

        // Build keywords from hashtags and category
        $keywords = [];
        if ($this->hashtags->isNotEmpty()) {
            $keywords = $this->hashtags->pluck('name')->toArray();
        }
        if ($this->category) {
            $keywords[] = $this->category->name;
        }
        $keywordsString = !empty($keywords) ? implode(', ', $keywords) : null;

        return new SEOData(
            title: $this->title,
            description: $description,
            image: $imageUrl,
            author: $this->user?->full_name ?? null,
            url: $canonicalUrl,
            published_time: $this->created_at,
            modified_time: $this->updated_at,
            type: 'article', // Article type for better SEO
            section: $this->category?->name, // Category as section
            tags: $this->hashtags->pluck('name')->toArray(), // Hashtags as tags
            // Note: keywords can be added via custom meta tags if needed
        );
    }
}
