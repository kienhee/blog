<?php

namespace App\Console\Commands;

use App\Mail\PostPublishedNotification;
use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class PublishScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scheduled posts and send notifications to authors';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();

        // Lấy các bài viết có scheduled_at đã đến và đang ở trạng thái scheduled
        $scheduledPosts = Post::where('status', Post::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->with('user')
            ->get();

        if ($scheduledPosts->isEmpty()) {
            $this->info('Không có bài viết nào cần đăng.');
            return Command::SUCCESS;
        }

        $count = 0;

        foreach ($scheduledPosts as $post) {
            // Cập nhật status thành published
            $post->status = Post::STATUS_PUBLISHED;
            $post->scheduled_at = null; // Xóa scheduled_at sau khi đăng
            $post->save();

            // Gửi email thông báo cho người tạo bài viết
            if ($post->user && $post->user->email) {
                try {
                    Mail::to($post->user->email)->send(
                        new PostPublishedNotification($post)
                    );
                } catch (\Exception $e) {
                    $this->error("Không thể gửi email cho bài viết ID {$post->id}: " . $e->getMessage());
                }
            }

            $count++;
            $this->info("Đã đăng bài viết: {$post->title} (ID: {$post->id})");
        }

        $this->info("Hoàn thành! Đã đăng {$count} bài viết.");

        return Command::SUCCESS;
    }
}
