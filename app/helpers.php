<?php

if (! function_exists('asset_admin_url')) {
    /**
     * Get the admin asset URL with versioning.
     *
     * @param  string  $path  url assets
     * @return string
     */
    function asset_admin_url($path)
    {
        return \App\System::asset_admin_url($path);
    }
}

if (! function_exists('asset_client_url')) {
    /**
     * Get the client asset URL with versioning.
     *
     * @param  string  $path  url assets
     * @return string
     */
    function asset_client_url($path)
    {
        return \App\System::asset_client_url($path);
    }
}

if (! function_exists('asset_shared_url')) {
    /**
     * Get public asset URL with versioning (for files in public directory)
     *
     * @param  string  $path  file path relative to public directory (e.g., 'css/post-content.css')
     * @return string
     */
    function asset_shared_url($path)
    {
        return \App\System::asset_shared_url($path);
    }
}

if (! function_exists('seed_version')) {
    /**
     * Handle SeedVersion
     *
     * @param  mixed  $tableName
     * @param  mixed  $version
     * @param  bool  $isTruncate
     * @return bool
     */
    function seed_version($tableName, $version = 1, $isTruncate = true)
    {
        return \App\System::SeedVersion($tableName, $version, $isTruncate);
    }
}

if (! function_exists('thumb_path')) {
    /**
     * Sinh đường dẫn thumbnail tương ứng với ảnh gốc.
     *
     * @param  string  $path  Đường dẫn ảnh gốc (vd: /storage/uploads/shares/Bài viết/post-slide-1.jpg)
     * @param  string  $prefix  Tên thư mục chứa thumbnail (vd: thumbs)
     */
    function thumb_path(string $path, string $prefix = 'thumbs'): string
    {
        // Chuẩn hóa dấu gạch chéo
        $path = str_replace('\\', '/', $path);

        // Tách phần thư mục và tên file
        $dir = dirname($path);
        $filename = basename($path);

        // Trả về đường dẫn thêm thư mục thumbs
        return "{$dir}/{$prefix}/{$filename}";
    }
}

/**
 * Kiểm tra url có được active không
 *
 * @param  mixed  $child
 * @return bool
 */
if (! function_exists('isOpenMenu')) {
    function isOpenMenu($child)
    {
        return route($child['url']) == url()->current();
    }
}
if (! function_exists('hasActiveChild')) {
    /**
     * Kiểm tra phần tử con có active hay không
     *
     * @param  array  $children
     * @return bool
     */
    function hasActiveChild($children = [])
    {
        foreach ($children as $child) {
            if (isOpenMenu($child)) {
                return true;
            }
        }
    }
}

if (! function_exists('calculateReadingTime')) {
    /**
     * Tính thời gian đọc ước tính
     */
    function calculateReadingTime($content)
    {
        // Loại bỏ HTML tags
        $text = strip_tags($content);
        // Đếm số từ (khoảng trắng)
        $wordCount = str_word_count($text);
        // Ước tính 200 từ/phút
        $minutes = max(1, ceil($wordCount / 200));

        return $minutes;
    }
}
