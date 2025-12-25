<?php

namespace Database\Seeders;

use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactSeeder extends Seeder
{
    private $tableName = 'contacts';

    private $version = 1;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (seed_version($this->tableName, $this->version)) {
            $faker = FakerFactory::create('vi_VN');

            // Danh sách các subject mẫu
            $subjects = [
                'Yêu cầu hỗ trợ kỹ thuật',
                'Câu hỏi về sản phẩm',
                'Đề xuất cải tiến',
                'Báo lỗi hệ thống',
                'Yêu cầu hợp tác',
                'Phản hồi dịch vụ',
                'Câu hỏi về giá cả',
                'Yêu cầu demo',
                'Đăng ký nhận bản tin',
                'Liên hệ quảng cáo',
                'Hỏi về chính sách',
                'Yêu cầu hoàn tiền',
                'Phản ánh chất lượng',
                'Góp ý cải thiện',
                'Yêu cầu tư vấn',
            ];

            $contacts = [];

            // Tạo 100 contact
            for ($i = 1; $i <= 100; $i++) {
                $createdAt = $faker->dateTimeBetween('-6 months', 'now');
                $updatedAt = $faker->dateTimeBetween($createdAt, 'now');

                // Random status (40% chưa xử lý, 20% đã liên hệ, 35% đã trả lời email, 5% spam)
                $statusRand = $faker->numberBetween(1, 100);
                if ($statusRand <= 40) {
                    $status = 0; // Chưa xử lý
                } elseif ($statusRand <= 60) {
                    $status = 1; // Đã liên hệ
                } elseif ($statusRand <= 95) {
                    $status = 2; // Đã trả lời email
                } else {
                    $status = 3; // Spam
                }

                // Tạo full_name
                $fullName = $faker->name();

                // Tạo email
                $email = $faker->unique()->safeEmail();

                // Random subject từ danh sách hoặc tạo mới
                $subject = $faker->boolean(70)
                    ? $faker->randomElement($subjects)
                    : $faker->sentence(rand(3, 6));

                // Tạo message
                $message = $faker->paragraphs(rand(2, 5), true);

                $contacts[] = [
                    'full_name' => $fullName,
                    'email' => $email,
                    'subject' => $subject,
                    'message' => $message,
                    'status' => $status,
                    'deleted_at' => null,
                    'created_at' => $createdAt->format('Y-m-d H:i:s'),
                    'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
                ];
            }

            // Insert in batches
            $chunks = array_chunk($contacts, 25);
            foreach ($chunks as $chunk) {
                DB::table($this->tableName)->insert($chunk);
            }

            $this->command->info('Đã tạo ' . count($contacts) . ' contact thành công.');
        }
    }
}
