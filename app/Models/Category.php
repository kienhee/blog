<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class Category extends Model
{
    use HasFactory, SoftDeletes, HasSEO;

    public $timestamps = true;

    protected $fillable = [
        'name',
        'thumbnail',
        'slug',
        'description',
        'parent_id',
        'order',
        'status',
    ];

    public static function renderOptions(array $tree, $selectedId = null, $level = 0)
    {
        foreach ($tree as $node) {
            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
            $class = ! empty($node['children']) ? 'fw-bold' : '';
            $selected = $node['id'] == $selectedId ? 'selected' : '';

            echo '<option value="'.$node['id'].'" class="'.$class.'" '.$selected.'>'
                .$indent.$node['name']
                .'</option>';

            if (! empty($node['children'])) {
                self::renderOptions($node['children'], $selectedId, $level + 1);
            }
        }
    }

    // Dùng riêng cho việc lấy ra menu cho navbar
    public function buildMenuClient(array $categories, $parent_id = null): array
    {
        $branch = [];
        foreach ($categories as $key => $category) {
            if ($category['parent_id'] == $parent_id) { // nếu là null thì  tức là cha
                unset($categories[$key]); // bỏ qua lần sau không duyệt nữa
                $children = $this->buildMenuClient($categories, $category['id']);
                // Use category route for better SEO
                $url = route('client.category', ['slug' => $category['slug']]);
                $branch[] = [
                    'title'=> $category['name'],
                    'url'=> $url,
                    'isRoute' => true,
                    'children' => $children,
                    'slug' => $category['slug'] ?? null,
                    'id' => $category['id'] ?? null
                ];
            }
        }

        return $branch;
    }

    /**
     * Get dynamic SEO data for the category
     */
    public function getDynamicSEOData(): SEOData
    {
        // Generate description if not available
        $description = $this->description;
        if (!$description) {
            $description = "Khám phá tất cả bài viết trong danh mục {$this->name}. Tìm hiểu thêm về chủ đề này và các bài viết liên quan.";
        }

        // Build full URL for image
        $imageUrl = null;
        if ($this->thumbnail) {
            $imageUrl = $this->thumbnail;
            if (!str_starts_with($imageUrl, 'http')) {
                $imageUrl = asset($imageUrl);
            }
        }

        // Build canonical URL
        $canonicalUrl = route('client.category', ['slug' => $this->slug], false);
        if (!str_starts_with($canonicalUrl, 'http')) {
            $canonicalUrl = url($canonicalUrl);
        }

        return new SEOData(
            title: $this->name,
            description: $description,
            image: $imageUrl,
            url: $canonicalUrl,
            type: 'website', // Category pages are websites
        );
    }
}
