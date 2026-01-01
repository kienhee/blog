<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Đăng bài theo lịch - chạy mỗi phút
Schedule::command('posts:publish-scheduled')->everyMinute();

// Tạo sitemap client hàng ngày
Schedule::command('sitemap:generate')->daily();

// Kiểm tra schedule test - chạy mỗi phút
Schedule::command('schedule:test')->everyMinute();
