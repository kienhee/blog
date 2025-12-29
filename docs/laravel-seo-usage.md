# Hướng dẫn sử dụng ralphjsmit/laravel-seo

## Tổng quan

Package `ralphjsmit/laravel-seo` đã được tích hợp vào dự án để quản lý SEO metadata tự động cho các trang và models.

## Cấu hình

File cấu hình: `config/seo.php`

### Các thiết lập quan trọng:

- **site_name**: Tên site (đã được set từ `config('app.name')`)
- **sitemap**: Đường dẫn đến sitemap (`/sitemap.xml`)
- **canonical_link**: Tự động thêm canonical link (đã bật)
- **robots**: Cấu hình robots meta tag

## Models đã tích hợp

### 1. Post Model

- Đã thêm trait `HasSEO`
- Method `getDynamicSEOData()` tự động lấy:
  - **Title**: Từ `$post->title`
  - **Description**: Từ `$post->description` hoặc 160 ký tự đầu của content
  - **Image**: Từ `$post->thumbnail`
  - **Author**: Từ `$post->user->full_name`
  - **URL**: Tự động generate từ route

### 2. Category Model

- Đã thêm trait `HasSEO`
- Method `getDynamicSEOData()` tự động lấy:
  - **Title**: Từ `$category->name`
  - **Description**: Từ `$category->description` hoặc mô tả mặc định
  - **Image**: Từ `$category->thumbnail`
  - **URL**: Tự động generate từ route

## Sử dụng trong Views

### Layout Master

File `resources/views/client/layouts/master.blade.php` đã được cập nhật:

```blade
{{-- SEO Meta Tags --}}
{!! seo()->for($seoModel ?? null) !!}
```

Helper `seo()->for()` sẽ:
- Nếu có `$seoModel`: Sử dụng SEO data từ model
- Nếu không có: Sử dụng fallback từ config

### Controllers

Các controllers đã được cập nhật để truyền model vào view:

#### PostController
```php
$seoModel = $postModel;
return view('client.pages.single', compact('post', 'postModel', 'seoModel', ...));
```

#### CategoryController
```php
$seoModel = $category;
return view('client.pages.category', compact('category', 'posts', 'seoModel'));
```

## Tùy chỉnh SEO cho từng Model

### Cách 1: Sử dụng getDynamicSEOData() (Đã implement)

Method này tự động lấy data từ model attributes:

```php
protected function getDynamicSEOData(): SEOData
{
    return new SEOData(
        title: $this->title,
        description: $this->description ?? 'Fallback description',
        image: $this->thumbnail ? asset($this->thumbnail) : null,
        author: $this->user?->full_name ?? null,
        url: route('client.post', ['slug' => $this->slug]),
    );
}
```

### Cách 2: Lưu SEO data vào database

Bạn có thể lưu SEO data riêng vào bảng `seo`:

```php
$post->seo->update([
    'title' => 'Custom SEO Title',
    'description' => 'Custom SEO Description',
    'image' => 'images/custom-image.jpg',
    'author' => 'Author Name',
    'robots' => 'noindex, nofollow', // Optional
    'canonical_url' => 'https://example.com/custom-url', // Optional
]);
```

**Lưu ý**: SEO data trong database sẽ được ưu tiên hơn `getDynamicSEOData()`.

## Các thẻ Meta được tạo tự động

Package sẽ tự động tạo các thẻ meta sau:

- `<title>` - Page title
- `<meta name="description">` - Meta description
- `<meta name="robots">` - Robots directive
- `<link rel="canonical">` - Canonical URL
- Open Graph tags (og:title, og:description, og:image, og:url, etc.)
- Twitter Card tags (twitter:card, twitter:title, etc.)

## Ví dụ sử dụng

### Trong Controller

```php
public function show($slug)
{
    $post = Post::where('slug', $slug)->firstOrFail();
    
    // Model sẽ tự động có SEO data
    $seoModel = $post;
    
    return view('posts.show', compact('post', 'seoModel'));
}
```

### Trong View

```blade
@extends('layouts.master')

{{-- SEO tags sẽ được tự động render từ $seoModel --}}
{{-- Không cần thêm gì, đã được xử lý trong master layout --}}

@section('content')
    <h1>{{ $post->title }}</h1>
    <p>{{ $post->content }}</p>
@endsection
```

## Tùy chỉnh cho trang không có Model

Đối với các trang không có model (như Home, Contact, About), bạn có thể:

### Cách 1: Sử dụng fallback từ config

Chỉ cần không truyền `$seoModel` vào view, package sẽ sử dụng fallback.

### Cách 2: Tạo SEOData trực tiếp trong Controller

```php
use RalphJSmit\Laravel\SEO\Support\SEOData;

public function home()
{
    $seoData = new SEOData(
        title: 'Trang chủ',
        description: 'Mô tả trang chủ',
        image: asset('images/home-og.jpg'),
    );
    
    return view('home', compact('seoData'));
}
```

Sau đó trong view:
```blade
{!! seo()->for($seoData ?? null) !!}
```

## Kiểm tra SEO Tags

Sau khi tích hợp, bạn có thể kiểm tra bằng cách:

1. View source của trang
2. Sử dụng browser DevTools (Elements tab)
3. Sử dụng các công cụ SEO như:
   - Google Rich Results Test
   - Facebook Sharing Debugger
   - Twitter Card Validator

## Lưu ý quan trọng

1. **Thumbnail/Image**: Đảm bảo đường dẫn image là đúng và accessible từ public folder
2. **Description**: Nên giữ dưới 160 ký tự để tối ưu SEO
3. **Title**: Nên giữ dưới 60 ký tự
4. **Canonical URL**: Tự động được tạo, đảm bảo route name đúng

## Tài liệu tham khảo

- [Package Documentation](https://github.com/ralphjsmit/laravel-seo)
- [Open Graph Protocol](https://ogp.me/)
- [Twitter Cards](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)

