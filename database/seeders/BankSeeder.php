<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        $banks = [
            [
                'code' => 'vcb',
                'name' => 'Ngan hang TMCP Ngoai Thuong Viet Nam',
                'short_name' => 'Vietcombank',
                'logo' => '/images/banks/vcb.png',
                'bg_color' => '#16A34A',
                'sort_order' => 10,
                'limit_request_per_minute' => 6,
                'metadata' => ['country' => 'VN', 'supports_api' => true],
            ],
            [
                'code' => 'acb',
                'name' => 'Ngân hàng TMCP Á Châu',
                'short_name' => 'ACB',
                'logo' => '/images/banks/acb.png',
                'bg_color' => '#2563EB',
                'sort_order' => 20,
                'limit_request_per_minute' => 6,
                'metadata' => ['country' => 'VN', 'supports_api' => true],
            ],
            [
                'code' => 'mb',
                'name' => 'Ngân hàng TMCP Quân đội',
                'short_name' => 'MB Bank',
                'logo' => '/images/banks/mb.png',
                'bg_color' => '#1D4ED8',
                'sort_order' => 30,
                'limit_request_per_minute' => 10,
                'metadata' => ['country' => 'VN', 'supports_api' => true],
            ],
            [
                'code' => 'tcb',
                'name' => 'Ngân hàng TMCP Kỹ Thương Việt Nam',
                'short_name' => 'Techcombank',
                'logo' => '/images/banks/tcb.png',
                'bg_color' => '#DC2626',
                'sort_order' => 40,
                'limit_request_per_minute' => 6,
                'metadata' => ['country' => 'VN', 'supports_api' => true],
            ],
            [
                'code' => 'bidv',
                'name' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam',
                'short_name' => 'BIDV',
                'logo' => '/images/banks/bidv.png',
                'bg_color' => '#0F766E',
                'sort_order' => 50,
                'limit_request_per_minute' => 6,
                'metadata' => ['country' => 'VN', 'supports_api' => true],
            ],
        ];

        foreach ($banks as $bank) {
            Bank::query()->updateOrCreate(
                ['code' => $bank['code']],
                $bank + ['is_active' => true],
            );
        }
    }
}
