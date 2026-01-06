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
Schedule::command('sitemap:generate')->dailyAt('0:00');

// Kiểm tra schedule test - chạy mỗi phút
Schedule::command('schedule:test')->everyMinute();

// Gửi email nhắc nhở kiểm tra và khóa tháng tài chính - chạy vào ngày 1 hàng tháng lúc 9:00
Schedule::command('finance:send-month-reminders')->monthlyOn(1, '9:00');
