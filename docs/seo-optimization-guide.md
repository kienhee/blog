# Hướng dẫn Tối ưu SEO với ralphjsmit/laravel-seo

## Tổng quan

Dự án đã được tích hợp đầy đủ các tính năng SEO nâng cao từ package `ralphjsmit/laravel-seo` để tối ưu hóa SEO cho website.

## Các tính năng đã tích hợp

### 1. ✅ SEO Metadata tự động cho Models

#### Post Model
- **Title**: Từ `$post->title`
- **Description**: Từ `$post->description` hoặc tự động extract 160 ký tự đầu từ content
- **Image**: Từ `$post->thumbnail` (Open Graph image)
- **Author**: Từ `$post->user->full_name`
- **Published Time**: `$post->created_at`
- **Modified Time**: `$post->updated_at`
- **Type**: `article` (cho Article schema)
- **Section**: Category name
- **Tags**: Hashtags của bài viết
- **Canonical URL**: Tự động generate

#### Category Model
- **Title**: Từ `$category->name`
- **Description**: Từ `$category->description` hoặc mô tả mặc định
- **Image**: Từ `$category->thumbnail`
- **Type**: `website`
- **Canonical URL**: Tự động generate

### 2. ✅ SEO cho các trang tĩnh

#### Homepage (`/`)
- Title: Từ config hoặc APP_NAME
- Description: Mô tả về blog
- Type: `website`

#### Tất cả bài viết (`/bai-viet`)
- Title: "Tất cả bài viết"
- Description: Mô tả về trang listing
- Type: `website`

#### Tìm kiếm (`/tim-kiem`)
- Title: "Tìm kiếm: {query}" hoặc "Tìm kiếm"
- Description: Kết quả tìm kiếm với số lượng bài viết
- Robots: `noindex, follow` (không index kết quả tìm kiếm)

#### Về chúng tôi (`/tac-gia`)
- Title: "Về chúng tôi"
- Description: Mô tả về trang
- Type: `website`

#### Liên hệ (`/lien-he`)
- Title: "Liên hệ"
- Description: Mô tả về trang liên hệ
- Robots: `noindex, follow` (không index trang liên hệ)

#### Hashtag (`/tag/{slug}`)
- Title: "Tag: {hashtag name}"
- Description: Số lượng bài viết trong tag
- Type: `website`

### 3. ✅ Open Graph Tags

Tất cả các trang đều có đầy đủ Open Graph tags:
- `og:title`
- `og:description`
- `og:image`
- `og:url`
- `og:type`
- `og:site_name`
- `og:author` (cho posts)
- `article:published_time` (cho posts)
- `article:modified_time` (cho posts)
- `article:section` (cho posts)
- `article:tag` (cho posts)

### 4. ✅ Twitter Card Tags

- `twitter:card` (summary_large_image)
- `twitter:title`
- `twitter:description`
- `twitter:image`
- `twitter:site` (nếu config trong `config/seo.php`)

### 5. ✅ Canonical URLs

Tất cả các trang đều có canonical URL để tránh duplicate content:
```html
<link rel="canonical" href="https://example.com/url" />
```

### 6. ✅ Robots Meta Tags

- Mặc định: `max-snippet:-1, max-image-preview:large, max-video-preview:-1`
- Trang tìm kiếm: `noindex, follow`
- Trang liên hệ: `noindex, follow`
- Có thể tùy chỉnh cho từng model

### 7. ✅ Schema.org Structured Data

Package tự động tạo structured data cho:
- **Article** (cho posts): với publishedTime, modifiedTime, author, section, tags
- **Website** (cho categories và trang tĩnh)

## Cấu hình

### File: `config/seo.php`

```php
'site_name' => config('app.name'), // Tên site cho Open Graph
'sitemap' => "/sitemap.xml", // Đường dẫn sitemap
'canonical_link' => true, // Tự động thêm canonical link
'description' => [
    'fallback' => 'Blog chia sẻ kiến thức...', // Fallback description
],
'author' => [
    'fallback' => config('app.name'), // Fallback author
],
'twitter' => [
    '@username' => null, // Twitter username (set nếu có)
],
```

## Cách sử dụng

### 1. SEO tự động từ Model

Khi bạn có Post hoặc Category model, SEO sẽ tự động được generate:

```php
// Trong Controller
$post = Post::where('slug', $slug)->first();
$seoModel = $post; // SEO tự động từ getDynamicSEOData()

return view('posts.show', compact('post', 'seoModel'));
```

### 2. SEO tùy chỉnh cho trang không có Model

```php
use RalphJSmit\Laravel\SEO\Support\SEOData;

$seoModel = new SEOData(
    title: 'Tiêu đề trang',
    description: 'Mô tả trang',
    url: route('page.route'),
    type: 'website',
    robots: 'noindex, follow', // Optional
);

return view('page', compact('seoModel'));
```

### 3. Override SEO từ Database

Bạn có thể lưu SEO data riêng vào database để override dynamic data:

```php
$post->seo->update([
    'title' => 'Custom SEO Title',
    'description' => 'Custom SEO Description',
    'image' => 'images/custom-og.jpg',
    'author' => 'Custom Author',
    'robots' => 'noindex, nofollow', // Optional
    'canonical_url' => 'https://example.com/custom-url', // Optional
]);
```

**Lưu ý**: SEO data trong database sẽ được ưu tiên hơn `getDynamicSEOData()`.

## Tối ưu hóa đã thực hiện

### 1. Description tự động
- Tự động extract từ content nếu không có description
- Loại bỏ HTML tags
- Giới hạn 160 ký tự (chuẩn SEO)
- Thêm "..." nếu bị cắt

### 2. Image URLs
- Tự động convert relative path sang absolute URL
- Hỗ trợ cả relative và absolute URLs

### 3. Canonical URLs
- Tự động generate từ route
- Convert sang absolute URL

### 4. Article Metadata
- Published time và modified time cho posts
- Section (category) cho posts
- Tags (hashtags) cho posts
- Author information

### 5. Robots Meta
- Tự động `noindex` cho trang tìm kiếm và liên hệ
- Có thể tùy chỉnh cho từng model

## Kiểm tra SEO

### 1. View Source
Xem source code của trang để kiểm tra các thẻ meta:
- `<title>`
- `<meta name="description">`
- `<meta property="og:*">`
- `<meta name="twitter:*">`
- `<link rel="canonical">`

### 2. Công cụ kiểm tra

#### Google Rich Results Test
https://search.google.com/test/rich-results

#### Facebook Sharing Debugger
https://developers.facebook.com/tools/debug/

#### Twitter Card Validator
https://cards-dev.twitter.com/validator

#### Schema.org Validator
https://validator.schema.org/

### 3. Google Search Console
- Submit sitemap: `/sitemap.xml`
- Kiểm tra indexing status
- Xem các lỗi SEO

## Best Practices

### 1. Title
- Giữ dưới 60 ký tự
- Bao gồm từ khóa chính
- Unique cho mỗi trang

### 2. Description
- Giữ dưới 160 ký tự
- Bao gồm từ khóa chính
- Hấp dẫn, kích thích click
- Unique cho mỗi trang

### 3. Images
- Kích thước tối thiểu: 1200x630px (Open Graph)
- Format: JPG hoặc PNG
- File size: < 1MB
- Accessible từ public folder

### 4. Canonical URLs
- Luôn có canonical URL
- Tránh duplicate content
- Sử dụng HTTPS

### 5. Robots Meta
- Sử dụng `noindex` cho:
  - Trang tìm kiếm
  - Trang liên hệ
  - Trang admin
  - Trang cá nhân (nếu cần)

## Troubleshooting

### SEO tags không hiển thị
1. Kiểm tra `$seoModel` đã được truyền vào view chưa
2. Kiểm tra `master.blade.php` có `{!! seo()->for($seoModel) !!}` hoặc `{!! seo()->render() !!}`
3. Clear cache: `php artisan view:clear`

### Image không hiển thị trong Open Graph
1. Kiểm tra đường dẫn image có đúng không
2. Kiểm tra image có accessible từ public folder không
3. Sử dụng absolute URL (https://...)

### Canonical URL sai
1. Kiểm tra route name có đúng không
2. Kiểm tra `APP_URL` trong `.env`
3. Clear config cache: `php artisan config:clear`

## Tài liệu tham khảo

- [Package Documentation](https://github.com/ralphjsmit/laravel-seo)
- [Open Graph Protocol](https://ogp.me/)
- [Twitter Cards](https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/abouts-cards)
- [Schema.org](https://schema.org/)
- [Google SEO Guide](https://developers.google.com/search/docs/beginner/seo-starter-guide)

