<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FinanceType;

class FinanceTypeSeeder extends Seeder
{
    private $tableName = 'finance_type';

    private $version = 2;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (seed_version($this->tableName, $this->version)) {
            $types = [
                ['id' => 1,  'name' => 'Đi chợ'],
                ['id' => 2,  'name' => 'Ăn uống'],
                ['id' => 3,  'name' => 'Xăng xe'],
                ['id' => 4,  'name' => 'Mua sắm'],
                ['id' => 5,  'name' => 'Quần áo'],
                ['id' => 6,  'name' => 'Giải trí'],
                ['id' => 7,  'name' => 'Y tế'],
                ['id' => 8,  'name' => 'Giáo dục'],
                ['id' => 9,  'name' => 'Điện nước'],
                ['id' => 10, 'name' => 'Internet/Điện thoại'],
                ['id' => 11, 'name' => 'Bảo hiểm'],
                ['id' => 12, 'name' => 'Thuê nhà'],
                ['id' => 13, 'name' => 'Mua đồ điện tử'],
                ['id' => 14, 'name' => 'Du lịch'],
                ['id' => 15, 'name' => 'Đầu tư'],
                ['id' => 16, 'name' => 'Khác'],
            ];

            foreach ($types as $type) {
                FinanceType::firstOrCreate(
                    ['id' => $type['id']],
                    ['name' => $type['name']]
                );
            }

            $this->command->info('Đã tạo ' . count($types) . ' loại chi tiêu.');
        }
    }
}
