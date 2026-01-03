<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\FinanceType;

class FinanceTypeSeeder extends Seeder
{
    private $tableName = 'finance_type';

    private $version = 1;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (seed_version($this->tableName, $this->version)) {
            $types = [
                'Ăn uống',
                'Xăng xe',
                'Mua sắm',
                'Quần áo',
                'Giải trí',
                'Y tế',
                'Giáo dục',
                'Điện nước',
                'Internet/Điện thoại',
                'Bảo hiểm',
                'Thuê nhà',
                'Mua đồ điện tử',
                'Du lịch',
                'Tiết kiệm',
                'Đầu tư',
                'Khác',
            ];

            foreach ($types as $name) {
                FinanceType::firstOrCreate(['name' => $name]);
            }

            $this->command->info('Đã tạo ' . count($types) . ' loại chi tiêu.');
        }
    }
}
