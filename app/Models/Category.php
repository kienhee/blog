<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

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
                // Use posts route with category filter instead of categories route
                $url = route('client.posts', ['category' => $category['slug']]);
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
}
