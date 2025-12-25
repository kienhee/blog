<?php

namespace Database\Seeders;

use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    private $tableName = 'categories';

    private $version = 1;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (seed_version($this->tableName, $this->version)) {
            $faker = FakerFactory::create('vi_VN');

            // Danh sách categories cha (parent_id = null)
            $parentCategories = [
                'Công nghệ',
                'Kinh doanh',
                'Giáo dục',
                'Sức khỏe',
                'Du lịch',
                'Ẩm thực',
                'Thể thao',
                'Giải trí',
            ];

            // Danh sách categories con cho mỗi category cha
            $childCategories = [
                'Công nghệ' => [
                    'Lập trình',
                    'Web Development',
                    'Mobile Development',
                    'AI & Machine Learning',
                    'Cloud Computing',
                ],
                'Kinh doanh' => [
                    'Khởi nghiệp',
                    'Marketing',
                    'Tài chính',
                    'Quản trị',
                    'Bán hàng',
                ],
                'Giáo dục' => [
                    'Học lập trình',
                    'Kỹ năng mềm',
                    'Ngoại ngữ',
                    'Khoa học',
                    'Lịch sử',
                ],
                'Sức khỏe' => [
                    'Dinh dưỡng',
                    'Tập luyện',
                    'Yoga',
                    'Tâm lý học',
                    'Sức khỏe tinh thần',
                ],
                'Du lịch' => [
                    'Trong nước',
                    'Nước ngoài',
                    'Du lịch bụi',
                    'Du lịch cao cấp',
                    'Ẩm thực địa phương',
                ],
                'Ẩm thực' => [
                    'Món Việt',
                    'Món Á',
                    'Món Âu',
                    'Đồ ngọt',
                    'Đồ uống',
                ],
                'Thể thao' => [
                    'Bóng đá',
                    'Bóng rổ',
                    'Tennis',
                    'Bơi lội',
                    'Chạy bộ',
                ],
                'Giải trí' => [
                    'Phim ảnh',
                    'Âm nhạc',
                    'Sách',
                    'Game',
                    'Podcast',
                ],
            ];

            $categories = [];
            $parentIds = [];

            // Tạo categories cha
            foreach ($parentCategories as $index => $name) {
                $slug = Str::slug($name);

                // Đảm bảo slug là unique
                $baseSlug = $slug;
                $counter = 1;
                while (DB::table($this->tableName)->where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter;
                    $counter++;
                }

                $createdAt = $faker->dateTimeBetween('-2 years', '-6 months');
                $updatedAt = $faker->dateTimeBetween($createdAt, 'now');

                $category = [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $faker->sentence(rand(8, 15)),
                    'thumbnail' => 'https://picsum.photos/400/300?random=' . ($index + 1),
                    'parent_id' => null,
                    'order' => $index + 1,
                    'created_at' => $createdAt->format('Y-m-d H:i:s'),
                    'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
                ];

                $categories[] = $category;
            }

            // Insert categories cha
            $chunks = array_chunk($categories, 10);
            foreach ($chunks as $chunk) {
                DB::table($this->tableName)->insert($chunk);
            }

            // Lấy lại parent IDs vừa tạo
            $parentNames = array_column($categories, 'name');
            $parentIds = DB::table($this->tableName)
                ->whereIn('name', $parentNames)
                ->whereNull('parent_id')
                ->orderBy('order', 'asc')
                ->pluck('id', 'name')
                ->toArray();

            // Tạo categories con
            $childCategoriesData = [];
            $orderCounter = 1;

            foreach ($childCategories as $parentName => $children) {
                if (!isset($parentIds[$parentName])) {
                    continue;
                }

                $parentId = $parentIds[$parentName];

                foreach ($children as $childIndex => $childName) {
                    $slug = Str::slug($childName);

                    // Đảm bảo slug là unique
                    $baseSlug = $slug;
                    $counter = 1;
                    while (DB::table($this->tableName)->where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }

                    $createdAt = $faker->dateTimeBetween('-1 year', 'now');
                    $updatedAt = $faker->dateTimeBetween($createdAt, 'now');

                    $childCategoriesData[] = [
                        'name' => $childName,
                        'slug' => $slug,
                        'description' => $faker->sentence(rand(6, 12)),
                        'thumbnail' => 'https://picsum.photos/400/300?random=' . ($orderCounter + 100),
                        'parent_id' => $parentId,
                        'order' => $childIndex + 1,
                        'created_at' => $createdAt->format('Y-m-d H:i:s'),
                        'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
                    ];

                    $orderCounter++;
                }
            }

            // Insert categories con
            if (!empty($childCategoriesData)) {
                $chunks = array_chunk($childCategoriesData, 10);
                foreach ($chunks as $chunk) {
                    DB::table($this->tableName)->insert($chunk);
                }
            }

            $this->command->info('Đã tạo ' . count($categories) . ' categories cha và ' . count($childCategoriesData) . ' categories con.');
        }
    }
}
