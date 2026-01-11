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

if (! function_exists('asset_shared_url_v2')) {
    /**
     * Get public asset URL with versioning for resources/shared files
     * This is a safer version that correctly handles paths relative to resources/shared directory
     *
     * @param  string  $path  file path relative to resources/shared directory (e.g., 'images/favicon.png')
     * @return string
     */
    function asset_shared_url_v2($path)
    {
        return \App\System::asset_shared_url_v2($path);
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
        $url = $child['url'] ?? null;
        
        // Xử lý URL đặc biệt cho tháng hiện tại
        if ($url === 'current_month_expense') {
            $url = getCurrentMonthExpenseUrl();
        } elseif ($url) {
            $url = route($url);
        } else {
            return false;
        }
        
        return $url == url()->current();
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

        return false;
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

if (! function_exists('renderCategoryDesktop')) {
    /**
     * Render category menu for desktop (recursive)
     *
     * @param  array  $items
     * @return string
     */
    function renderCategoryDesktop($items)
    {
        $html = '';
        foreach ($items as $item) {
            $hasChildren = !empty($item['children']) && count($item['children']) > 0;

            if ($hasChildren) {
                $html .= '<li class="dropdown-item-parent">';
                $html .= '<a class="dropdown-item dropdown-toggle" href="' . e($item['url']) . '">';
                $html .= '<span>' . e($item['title']) . '</span>';
                $html .= '<i class="bx bx-chevron-right float-end"></i>';
                $html .= '</a>';
                $html .= '<ul class="dropdown-menu dropdown-submenu">';
                $html .= renderCategoryDesktop($item['children']);
                $html .= '</ul>';
                $html .= '</li>';
            } else {
                $html .= '<li><a class="dropdown-item" href="' . e($item['url']) . '"><span>' . e($item['title']) . '</span></a></li>';
            }
        }

        return $html;
    }
}

if (! class_exists('CategoryMenuBuilder')) {
    /**
     * Class to build mobile category menu structure with stack navigation
     */
    class CategoryMenuBuilder
    {
        public $panelId = 1;
        public $panels = [];
        public $rootItems = '';

        /**
         * Build mobile menu structure recursively
         *
         * @param  array  $items
         * @param  int  $level
         * @param  int  $parentId
         * @return string
         */
        public function buildMobileMenu($items, $level = 0, $parentId = 0)
        {
            $currentLevelItems = '';

            foreach ($items as $item) {
                $hasChildren = !empty($item['children']) && count($item['children']) > 0;

                if ($hasChildren) {
                    $currentPanelId = $this->panelId++;

                    // Add to root items if level 0, otherwise add to current level items
                    $itemHtml = '<li class="menu-stack-item has-children" data-target="' . $currentPanelId . '">';
                    $itemHtml .= '<span>' . e($item['title']) . '</span>';
                    $itemHtml .= '<i class="bx bx-chevron-right"></i>';
                    $itemHtml .= '</li>';

                    if ($level === 0) {
                        $this->rootItems .= $itemHtml;
                    } else {
                        $currentLevelItems .= $itemHtml;
                    }

                    // Recursively build children for this panel
                    $childrenItems = $this->buildMobileMenu($item['children'], $level + 1, $currentPanelId);

                    // Create panel HTML
                    $panelHtml = '<div class="menu-stack-panel" data-panel-id="' . $currentPanelId . '" data-level="' . ($level + 1) . '" data-parent="' . $parentId . '">';
                    $panelHtml .= '<div class="menu-stack-header">';
                    $panelHtml .= '<button class="menu-stack-back" type="button">';
                    $panelHtml .= '<i class="bx bx-chevron-left"></i>';
                    $panelHtml .= '</button>';
                    $panelHtml .= '<span class="menu-stack-title">' . e($item['title']) . '</span>';
                    $panelHtml .= '</div>';
                    $panelHtml .= '<ul class="menu-stack-list">';
                    $panelHtml .= $childrenItems;
                    $panelHtml .= '</ul>';
                    $panelHtml .= '</div>';

                    $this->panels[] = $panelHtml;
                } else {
                    // Add item without children
                    $itemHtml = '<li class="menu-stack-item">';
                    $itemHtml .= '<a href="' . e($item['url']) . '"><span>' . e($item['title']) . '</span></a>';
                    $itemHtml .= '</li>';

                    if ($level === 0) {
                        $this->rootItems .= $itemHtml;
                    } else {
                        $currentLevelItems .= $itemHtml;
                    }
                }
            }

            return $currentLevelItems;
        }
    }
}

if (! function_exists('get_posts_per_page')) {
    /**
     * Lấy số lượng bài viết mỗi trang từ settings
     *
     * @return int
     */
    function get_posts_per_page(): int
    {
        return (int) \App\Models\Setting::getValue('posts_per_page', 15);
    }
}

if (! function_exists('getCurrentMonthExpenseUrl')) {
    /**
     * Lấy URL của tháng hiện tại để thêm chi tiêu
     *
     * @return string
     */
    function getCurrentMonthExpenseUrl(): string
    {
        $currentYear = now()->year;
        $currentMonth = now()->month;
        
        // Tìm hoặc tạo năm hiện tại
        $financeYearRepository = app(\App\Repositories\FinanceYearRepository::class);
        $year = $financeYearRepository->firstOrCreate(
            ['year' => $currentYear],
            ['target' => []]
        );
        
        return route('admin.finance.years.months.show', [
            'yearId' => $year->id,
            'month' => $currentMonth
        ]);
    }
}
