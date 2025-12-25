<?php

namespace Database\Seeders;

use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    private $tableName = 'posts';

    private $version = 1;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (seed_version($this->tableName, $this->version)) {
            $faker = FakerFactory::create('vi_VN');

            // Lấy danh sách user IDs
            $userIds = DB::table('users')->pluck('id')->toArray();
            if (empty($userIds)) {
                $this->command->warn('Không có user nào. Vui lòng chạy UserSeeder trước.');

                return;
            }

            // Lấy danh sách category IDs
            $categoryIds = DB::table('categories')->pluck('id')->toArray();
            if (empty($categoryIds)) {
                $this->command->warn('Không có category nào. Vui lòng tạo categories trước.');

                return;
            }

            // Lấy danh sách hashtag IDs
            $hashtagIds = DB::table('hash_tags')->pluck('id')->toArray();

            $posts = [];
            $postHashtags = [];

            // Tạo 50 bài viết
            for ($i = 1; $i <= 50; $i++) {
                $createdAt = $faker->dateTimeBetween('-1 year', 'now');
                $updatedAt = $faker->dateTimeBetween($createdAt, 'now');

                // Tạo title và slug
                $title = $faker->sentence(rand(4, 8));
                $slug = Str::slug($title);

                // Đảm bảo slug là unique
                $baseSlug = $slug;
                $counter = 1;
                while (DB::table($this->tableName)->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                // Random status (70% published, 30% draft)
                $status = $faker->boolean(70) ? 'published' : 'draft';

                // Random scheduled_at (20% có scheduled, 80% không)
                $scheduledAt = null;
                if ($faker->boolean(20)) {
                    $scheduledAt = $faker->dateTimeBetween('now', '+1 month');
                }

                // Random allow_comment (80% cho phép, 20% không)
                $allowComment = $faker->boolean(80) ? 1 : 0;

                // Random thumbnail (sử dụng placeholder images)
                $thumbnail = 'https://picsum.photos/800/600?random='.$i;

                // Tạo content với HTML
                $content = $this->generatePostContent($faker);

                // Tạo description
                $description = $faker->sentence(rand(10, 20));

                $posts[] = [
                    'user_id' => $faker->randomElement($userIds),
                    'category_id' => $faker->randomElement($categoryIds),
                    'thumbnail' => $thumbnail,
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $content,
                    'description' => $description,
                    'status' => $status,
                    'scheduled_at' => $scheduledAt ? $scheduledAt->format('Y-m-d H:i:s') : null,
                    'allow_comment' => $allowComment,
                    'deleted_at' => null,
                    'created_at' => $createdAt->format('Y-m-d H:i:s'),
                    'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
                ];
            }

            // Insert posts in batches
            $chunks = array_chunk($posts, 25);
            foreach ($chunks as $chunk) {
                DB::table($this->tableName)->insert($chunk);
            }

            // Lấy lại tất cả post IDs vừa tạo (theo thứ tự created_at)
            $slugs = array_column($posts, 'slug');
            $postIds = DB::table($this->tableName)
                ->whereIn('slug', $slugs)
                ->orderBy('created_at', 'asc')
                ->pluck('id')
                ->toArray();

            // Gán hashtags cho các bài viết (mỗi bài viết có 2-5 hashtags)
            if (! empty($hashtagIds)) {
                foreach ($postIds as $postId) {
                    $numHashtags = rand(2, 5);
                    $selectedHashtags = $faker->randomElements($hashtagIds, min($numHashtags, count($hashtagIds)));

                    foreach ($selectedHashtags as $hashtagId) {
                        $postHashtags[] = [
                            'post_id' => $postId,
                            'hashtag_id' => $hashtagId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                // Insert post_hashtags in batches
                if (! empty($postHashtags)) {
                    $hashtagChunks = array_chunk($postHashtags, 50);
                    foreach ($hashtagChunks as $chunk) {
                        DB::table('post_hashtags')->insert($chunk);
                    }
                }
            }

            $this->command->info('Đã tạo '.count($posts).' bài viết thành công!');
        }
    }

    /**
     * Tạo nội dung bài viết với HTML
     */
    private function generatePostContent($faker): string
    {
        $paragraphs = [];
        $numParagraphs = rand(3, 8);

        for ($i = 0; $i < $numParagraphs; $i++) {
            $paragraph = '<p>'.$faker->paragraph(rand(3, 6)).'</p>';
            $paragraphs[] = $paragraph;

            // Thỉnh thoảng thêm heading
            if ($faker->boolean(30) && $i > 0) {
                $headingLevel = rand(2, 3);
                $heading = '<h'.$headingLevel.'>'.$faker->sentence(rand(4, 8)).'</h'.$headingLevel.'>';
                array_splice($paragraphs, -1, 0, [$heading]);
            }

            // Thỉnh thoảng thêm list
            if ($faker->boolean(20)) {
                $listItems = [];
                $numItems = rand(3, 6);
                for ($j = 0; $j < $numItems; $j++) {
                    $listItems[] = '<li>'.$faker->sentence(rand(5, 10)).'</li>';
                }
                $list = '<ul>'.implode('', $listItems).'</ul>';
                $paragraphs[] = $list;
            }
        }

        return implode('', $paragraphs);
    }
}
