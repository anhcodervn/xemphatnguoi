<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $pages = [
            'about_page' => [
                'title' => 'Giới thiệu',
                'excerpt' => 'AutoCron là nền tảng SaaS giúp tạo, chạy và giám sát HTTP Cron Jobs với logs, alerts và quota theo gói.',
                'seo_title' => 'Giới thiệu về AutoCron',
                'seo_description' => 'Tìm hiểu AutoCron là gì, dành cho ai và cách nền tảng hỗ trợ vận hành HTTP cron jobs an toàn, rõ ràng và dễ mở rộng.',
                'content' => [
                    $this->heading('Giới thiệu về AutoCron', 2),
                    $this->paragraph('AutoCron là nền tảng SaaS cho thuê và quản lý HTTP Cron Jobs, giúp người dùng tạo các tác vụ gọi HTTP theo lịch mà không cần tự vận hành worker và scheduler riêng.'),
                    $this->paragraph('Người dùng có thể cấu hình URL, method, headers, body, timeout, retry, expected status code, expected body và theo dõi trạng thái chạy tập trung ngay trong dashboard.'),
                    $this->heading('Đối tượng phù hợp', 2),
                    $this->list([
                        'Developer cần ping API định kỳ, healthcheck hoặc đồng bộ dữ liệu.',
                        'Đội vận hành muốn giám sát endpoint và nhận cảnh báo khi task lỗi.',
                        'Doanh nghiệp cần hệ thống cron HTTP có quota, logs, alerts và queue rõ ràng.',
                        'Agency hoặc team triển khai nhiều dự án muốn gom HTTP tasks về một nơi quản lý.',
                    ]),
                    $this->heading('Những gì hệ thống hỗ trợ', 2),
                    $this->list([
                        'Tạo và quản lý HTTP Cron Jobs theo interval hoặc cron expression.',
                        'Ghi logs cho từng lần chạy với request/response preview, duration và lỗi chi tiết.',
                        'Phân quyền theo API key với giới hạn request, retention logs và priority queue.',
                        'Gửi cảnh báo qua Discord, Telegram, Webhook hoặc Email khi fail và khi recovered.',
                        'Chặn SSRF với scheme, IP, port và redirect nguy hiểm trước khi request.',
                    ]),
                    $this->heading('Giá trị mang lại', 2),
                    $this->paragraph('AutoCron giúp giảm công sức vận hành tác vụ định kỳ, chuẩn hóa log và giám sát, đồng thời giữ quyền kiểm soát rõ ràng theo từng gói dịch vụ và từng người dùng.'),
                ],
            ],
            'terms_page' => [
                'title' => 'Điều khoản sử dụng',
                'excerpt' => 'Các quy định về quyền, trách nhiệm và giới hạn sử dụng dịch vụ AutoCron đối với người dùng.',
                'seo_title' => 'Điều khoản sử dụng AutoCron',
                'seo_description' => 'Quy định sử dụng dịch vụ AutoCron, trách nhiệm người dùng, cấu hình cron jobs và các trường hợp hệ thống có thể giới hạn tài khoản.',
                'content' => [
                    $this->heading('1. Giới thiệu dịch vụ', 2),
                    $this->paragraph('AutoCron cung cấp công cụ tạo, lập lịch và chạy HTTP Cron Jobs theo cấu hình của người dùng, bao gồm logs, quota, alert và các cơ chế bảo vệ an toàn hạ tầng.'),
                    $this->heading('2. Trách nhiệm của người dùng', 2),
                    $this->list([
                        'Cung cấp thông tin tài khoản chính xác và bảo mật thông tin đăng nhập.',
                        'Tự chịu trách nhiệm với URL đích, headers, body và dữ liệu gửi qua cron jobs.',
                        'Đảm bảo việc sử dụng dịch vụ phù hợp với pháp luật và điều khoản hệ thống.',
                    ]),
                    $this->heading('3. Các hành vi bị cấm', 2),
                    $this->list([
                        'Lạm dụng hệ thống để quét, tấn công hoặc gửi request trái phép tới bên thứ ba.',
                        'Cố tình vượt quota, gây nghẽn hạ tầng hoặc khai thác lỗi hệ thống.',
                        'Sử dụng dịch vụ cho mục đích vi phạm pháp luật hoặc phát tán nội dung độc hại.',
                    ]),
                    $this->heading('4. Gói dịch vụ và giới hạn', 2),
                    $this->paragraph('Giới hạn số lượng cron jobs, khoảng chạy tối thiểu, retention logs, quota ngày/tháng và tính năng nâng cao được xác định theo gói dịch vụ đang hoạt động.'),
                    $this->heading('5. Tạm khóa hoặc chấm dứt tài khoản', 2),
                    $this->paragraph('AutoCron có quyền tạm khóa hoặc chấm dứt tài khoản nếu phát hiện hành vi bất thường, vi phạm điều khoản hoặc gây rủi ro tới hệ thống và người dùng khác.'),
                ],
            ],
            'api_usage_policy' => [
                'title' => 'Chính sách sử dụng dịch vụ',
                'excerpt' => 'Quy định về HTTP Cron Jobs, quota, alerts, queue, bảo mật và các giới hạn sử dụng trên AutoCron.',
                'seo_title' => 'Chính sách sử dụng dịch vụ AutoCron',
                'seo_description' => 'Quy định về quota, queue, alerts, logs và SSRF protection khi sử dụng dịch vụ AutoCron.',
                'content' => [
                    $this->heading('1. Cron jobs và lịch chạy', 2),
                    $this->paragraph('Người dùng được tạo cron jobs trong phạm vi giới hạn của dịch vụ. Hệ thống có thể từ chối cấu hình interval quá thấp hoặc tính năng chưa được hỗ trợ.'),
                    $this->heading('2. Quota và logs', 2),
                    $this->paragraph('Mỗi gói có thể áp dụng quota theo ngày hoặc theo tháng, đồng thời giới hạn số logs trên mỗi task và thời gian lưu trữ logs.'),
                    $this->heading('3. Alerts và kênh thông báo', 2),
                    $this->paragraph('Kênh cảnh báo chỉ khả dụng theo tính năng của từng gói. Người dùng có trách nhiệm cấu hình webhook, Discord hoặc Telegram chính xác để nhận thông báo.'),
                    $this->heading('4. SSRF protection', 2),
                    $this->paragraph('AutoCron chặn localhost, private IP, metadata IP, port nguy hiểm và các redirect không an toàn nhằm bảo vệ hạ tầng và ngăn hành vi lạm dụng.'),
                    $this->heading('5. Xử lý lỗi và giới hạn trách nhiệm', 2),
                    $this->paragraph('Hệ thống ghi nhận lỗi timeout, SSL, DNS, connection error, body mismatch hoặc status code mismatch để hỗ trợ người dùng xử lý, nhưng không chịu trách nhiệm với endpoint bên thứ ba bị lỗi hoặc dữ liệu đầu cuối của người dùng.'),
                ],
            ],
        ];

        foreach ($pages as $baseKey => $page) {
            $this->upsertIfEmpty("{$baseKey}_title", $page['title'], 'string');
            $this->upsertIfEmpty("{$baseKey}_excerpt", $page['excerpt'], 'string');
            $this->upsertIfEmpty("{$baseKey}_seo_title", $page['seo_title'], 'string');
            $this->upsertIfEmpty("{$baseKey}_seo_description", $page['seo_description'], 'string');
            $this->upsertIfEmpty("{$baseKey}_content", $page['content'], 'json');
            $this->upsertIfEmpty("{$baseKey}_is_published", '1', 'boolean');
        }
    }

    public function down(): void
    {
        // Keep seeded content to avoid deleting user-facing public pages on rollback.
    }

    /**
     * @param  array<int, mixed>  $content
     */
    protected function upsertIfEmpty(string $key, string|array $content, string $type): void
    {
        $setting = Setting::query()->where('key', $key)->first();

        if ($setting !== null && $setting->value !== null && $setting->value !== '' && $setting->value !== '[]') {
            return;
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($content)
                    ? json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $content,
                'type' => $type,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function heading(string $text, int $level = 2): array
    {
        return [
            'type' => 'heading',
            'level' => $level,
            'children' => [
                ['text' => $text],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function paragraph(string $text): array
    {
        return [
            'type' => 'paragraph',
            'children' => [
                ['text' => $text],
            ],
        ];
    }

    /**
     * @param  array<int, string>  $items
     * @return array<string, mixed>
     */
    protected function list(array $items): array
    {
        return [
            'type' => 'list',
            'ordered' => false,
            'items' => array_map(
                fn (string $item) => [
                    ['text' => $item],
                ],
                $items,
            ),
        ];
    }
};
