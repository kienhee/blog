<?php

return [
    [
        'title' => 'Dashboard',
        'icon' => 'bx-home-circle',
        'url' => 'admin.dashboard.analytics',
    ],
    [
        'title' => 'Module Bài viết',
        'divider' => true,
    ],
    [
        'title' => 'Quản lý danh mục',
        'icon' => 'bx-category',
        'children' => [
            [
                'title' => 'Danh sách',
                'url' => 'admin.categories.list',
            ],
            [
                'title' => 'Thêm mới',
                'url' => 'admin.categories.create',
            ],
        ],
    ],
    [
        'title' => 'Quản lý bài viết',
        'icon' => 'bx-file',
        'children' => [
            [
                'title' => 'Danh sách',
                'url' => 'admin.posts.list',
            ],
            [
                'title' => 'Thêm mới',
                'url' => 'admin.posts.create',
            ],
        ],
    ],
    [
        'title' => 'Quản lý hashtag',
        'icon' => 'bx-hash',
        'children' => [
            [
                'title' => 'Danh sách',
                'url' => 'admin.hashtags.list',
            ],
            [
                'title' => 'Thêm mới',
                'url' => 'admin.hashtags.create',
            ],
        ],
    ],
    [
        'title' => 'Module Người dùng',
        'divider' => true,
    ],
    [
        'title' => 'Quản lý người dùng',
        'icon' => 'bx-user',
        'children' => [
            [
                'title' => 'Danh sách',
                'url' => 'admin.users.list',
            ],
            [
                'title' => 'Thêm mới',
                'url' => 'admin.users.create',
            ],
        ],
    ],
    [
        'title' => 'Hệ thống & Cài đặt',
        'divider' => true,
    ],
    [
        'title' => 'Liên hệ',
        'icon' => 'bx-envelope',
        'url' => 'admin.contacts.list',
        // 'badgeId' => 'admin_contacts_list',
    ],
    [
        'title' => 'Media',
        'icon' => 'bx-image',
        'url' => 'admin.media',
    ],
    [
        'title' => 'Cài đặt',
        'icon' => 'bx-cog',
        'url' => 'admin.settings.index',
    ],

];
