<?php

return [
    [
        'title' => 'Dashboard',
        'icon' => 'bx-home-circle',
        'url' => 'admin.dashboard.analytics',
    ],
    // [
    //     'title' => 'Module Bài viết',
    //     'divider' => true,
    // ],
    [
        'title' => 'Quản lý danh mục',
        'icon' => 'bx-category',
        'permission' => 'category.read', // Permission để hiển thị menu cha
        'children' => [
            [
                'title' => 'Danh sách',
                'url' => 'admin.categories.list',
                'permission' => 'category.read',
            ],
            [
                'title' => 'Thêm mới',
                'url' => 'admin.categories.create',
                'permission' => 'category.create',
            ],
        ],
    ],
    [
        'title' => 'Quản lý bài viết',
        'icon' => 'bx-file',
        'permission' => 'post.read',
        'children' => [
            [
                'title' => 'Danh sách',
                'url' => 'admin.posts.list',
                'permission' => 'post.read',
            ],
            [
                'title' => 'Thêm mới',
                'url' => 'admin.posts.create',
                'permission' => 'post.create',
            ],
        ],
    ],
    [
        'title' => 'Quản lý hashtag',
        'icon' => 'bx-hash',
        'permission' => 'hashtag.read',
        'children' => [
            [
                'title' => 'Danh sách',
                'url' => 'admin.hashtags.list',
                'permission' => 'hashtag.read',
            ],
            [
                'title' => 'Thêm mới',
                'url' => 'admin.hashtags.create',
                'permission' => 'hashtag.create',
            ],
        ],
    ],
    [
        'title' => 'Quản lý newsletter',
        'icon' => 'bx-envelope',
        'url' => 'admin.newsletters.list',
        'permission' => 'newsletter.read',
    ],
    [
        'title' => 'Quản lý bình luận',
        'icon' => 'bx-message-dots',
        'url' => 'admin.comments.list',
        'permission' => 'comment.read',
        'badgeId' => 'admin_comments_pending',
    ],
    // [
    //     'title' => 'Module Người dùng',
    //     'divider' => true,
    // ],
    [
        'title' => 'Quản lý người dùng',
        'icon' => 'bx-user',
        'permission' => 'user.read',
        'children' => [
            [
                'title' => 'Danh sách',
                'url' => 'admin.users.list',
                'permission' => 'user.read',
            ],
            [
                'title' => 'Thêm mới',
                'url' => 'admin.users.create',
                'permission' => 'user.create',
            ],
        ],
    ],
    [
        'title' => 'Quản lý vai trò',
        'icon' => 'bx-shield',
        'permission' => 'role.read',
        'children' => [
            [
                'title' => 'Danh sách',
                'url' => 'admin.roles.list',
                'permission' => 'role.read',
            ],
            [
                'title' => 'Thêm mới',
                'url' => 'admin.roles.create',
                'permission' => 'role.create',
            ],
        ],
    ],
    // [
    //     'title' => 'Hệ thống & Cài đặt',
    //     'divider' => true,
    // ],
    [
        'title' => 'Liên hệ',
        'icon' => 'bx-envelope',
        'url' => 'admin.contacts.list',
        'permission' => 'contact.read',
        // 'badgeId' => 'admin_contacts_list',
    ],
    // [
    //     'title' => 'Media',
    //     'icon' => 'bx-image',
    //     'url' => 'admin.media',
    // ],
    [
        'title' => 'Quản lý tài khoản',
        'icon' => 'bx-user',
        'url' => 'admin.accounts.list',
        'permission' => 'account.read',
    ],
    [
        'title' => 'Danh sách chi tiêu',
        'icon' => 'bx-wallet',
        'url' => 'admin.finance.years.list',
    ],
    [
        'title' => 'Cài đặt',
        'icon' => 'bx-cog',
        'url' => 'admin.settings.index',
        'permission' => 'setting.read',
    ],

];
