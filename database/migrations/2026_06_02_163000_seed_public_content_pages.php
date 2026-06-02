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
                'excerpt' => 'ApibankVN là nền tảng hỗ trợ tạo lệnh nạp tiền, quét giao dịch ngân hàng và xác nhận chuyển khoản tự động qua API và webhook.',
                'seo_title' => 'Giới thiệu về ApibankVN',
                'seo_description' => 'Tìm hiểu ApibankVN là gì, dành cho ai và hệ thống hỗ trợ những nghiệp vụ nào trong vận hành API banking.',
                'content' => [
                    $this->heading('Giới thiệu về ApibankVN', 2),
                    $this->paragraph('ApibankVN là nền tảng cung cấp giải pháp API hỗ trợ tạo lệnh nạp tiền, theo dõi giao dịch ngân hàng và xác nhận thanh toán tự động cho cá nhân, doanh nghiệp và các hệ thống trực tuyến.'),
                    $this->paragraph('Hệ thống được xây dựng để phục vụ các nhu cầu như tạo lệnh nạp tiền qua chuyển khoản, đối soát giao dịch tự động theo nội dung thanh toán, nhận kết quả qua API hoặc webhook và quản lý API key, bank account, lịch sử request tập trung.'),
                    $this->heading('Đối tượng phù hợp', 2),
                    $this->list([
                        'Website dịch vụ số, hệ thống thành viên và đại lý.',
                        'Game, ứng dụng, nền tảng nội bộ cần tự động hóa thanh toán.',
                        'Doanh nghiệp muốn giảm thao tác kiểm tra giao dịch thủ công.',
                        'Đội vận hành cần một dashboard tập trung để theo dõi API, webhook và bank account.',
                    ]),
                    $this->heading('Những gì hệ thống hỗ trợ', 2),
                    $this->list([
                        'Tạo lệnh nạp tiền qua API và sinh nội dung chuyển khoản riêng cho từng giao dịch.',
                        'Quét giao dịch ngân hàng, đối soát tự động và cập nhật trạng thái đơn nạp.',
                        'Gửi webhook khi trạng thái giao dịch thay đổi.',
                        'Quản lý API key theo quyền, IP whitelist và log request tập trung.',
                        'Theo dõi webhook, bank account, queue và các thao tác người dùng trong một hệ thống thống nhất.',
                    ]),
                    $this->heading('Giá trị mang lại', 2),
                    $this->paragraph('ApibankVN giúp rút ngắn thời gian xác nhận thanh toán, nâng cao độ chính xác khi đối soát, giảm thao tác thủ công và giúp đội kỹ thuật dễ tích hợp hơn vào hệ thống hiện có.'),
                    $this->heading('Vận hành và hỗ trợ', 2),
                    $this->paragraph('Chúng tôi định hướng xây dựng hệ thống ổn định, dễ mở rộng và phù hợp với các mô hình API banking thực tế. Nếu cần hỗ trợ tích hợp, đối soát giao dịch hoặc xử lý lỗi vận hành, người dùng có thể liên hệ qua email, hotline hoặc các kênh hỗ trợ được công bố trên hệ thống.'),
                ],
            ],
            'terms_page' => [
                'title' => 'Điều khoản sử dụng',
                'excerpt' => 'Các quy định về quyền, trách nhiệm và giới hạn sử dụng dịch vụ ApibankVN đối với người dùng và đối tác tích hợp.',
                'seo_title' => 'Điều khoản sử dụng ApibankVN',
                'seo_description' => 'Quy định sử dụng dịch vụ, trách nhiệm người dùng, API key, thanh toán và các trường hợp tạm khóa tài khoản trên ApibankVN.',
                'content' => [
                    $this->heading('1. Giới thiệu dịch vụ', 2),
                    $this->paragraph('ApibankVN cung cấp công cụ hỗ trợ tạo lệnh nạp tiền, theo dõi giao dịch ngân hàng, đối soát thanh toán và xử lý dữ liệu liên quan theo cấu hình của người dùng.'),
                    $this->heading('2. Điều kiện sử dụng tài khoản', 2),
                    $this->paragraph('Người dùng có trách nhiệm cung cấp thông tin tài khoản chính xác, bảo mật thông tin đăng nhập, API key, secret key, webhook URL và các cấu hình liên quan.'),
                    $this->heading('3. Trách nhiệm của người dùng', 2),
                    $this->list([
                        'Tự chịu trách nhiệm về dữ liệu tài khoản ngân hàng, API key và webhook của mình.',
                        'Đảm bảo việc sử dụng dịch vụ phù hợp với pháp luật hiện hành.',
                        'Theo dõi, kiểm tra cấu hình tích hợp và xử lý kịp thời các cảnh báo hệ thống.',
                    ]),
                    $this->heading('4. Các hành vi bị cấm', 2),
                    $this->list([
                        'Sử dụng dịch vụ cho hành vi lừa đảo, rửa tiền, spam, đánh cắp dữ liệu hoặc hoạt động trái pháp luật.',
                        'Chia sẻ, bán lại hoặc công khai API key, secret key lên mã nguồn, nhóm chat hoặc diễn đàn công khai.',
                        'Cố ý khai thác lỗi hệ thống, vượt quyền truy cập hoặc gây ảnh hưởng đến hạ tầng vận hành.',
                    ]),
                    $this->heading('5. Gói dịch vụ và thanh toán', 2),
                    $this->paragraph('Người dùng cần thanh toán đúng theo gói dịch vụ đã chọn. Việc kích hoạt, gia hạn hoặc nâng cấp gói được thực hiện theo chính sách thanh toán và trạng thái ghi nhận của hệ thống.'),
                    $this->heading('6. API key, token và quyền truy cập', 2),
                    $this->paragraph('Mọi request phát sinh từ API key của người dùng được xem là do người dùng thực hiện. Người dùng có trách nhiệm tự bảo mật API key, secret và cấu hình IP whitelist nếu có.'),
                    $this->heading('7. Giới hạn trách nhiệm của hệ thống', 2),
                    $this->paragraph('ApibankVN không chịu trách nhiệm đối với thiệt hại phát sinh do người dùng cấu hình sai webhook, để lộ API key, nhập sai thông tin hoặc sử dụng dịch vụ sai mục đích.'),
                    $this->heading('8. Tạm khóa hoặc chấm dứt tài khoản', 2),
                    $this->paragraph('Hệ thống có quyền tạm khóa hoặc chấm dứt tài khoản nếu phát hiện hành vi bất thường, vi phạm điều khoản hoặc gây rủi ro đến hoạt động chung của nền tảng.'),
                    $this->heading('9. Thay đổi dịch vụ', 2),
                    $this->paragraph('ApibankVN có thể điều chỉnh tính năng, giới hạn gói, quy trình vận hành hoặc thời gian bảo trì để phù hợp với yêu cầu hệ thống và nhà cung cấp liên quan.'),
                    $this->heading('10. Liên hệ hỗ trợ', 2),
                    $this->paragraph('Khi cần hỗ trợ, người dùng vui lòng cung cấp email tài khoản, mã đơn hàng hoặc mã giao dịch liên quan để đội ngũ vận hành có thể xử lý nhanh hơn.'),
                ],
            ],
            'api_usage_policy' => [
                'title' => 'Chính sách sử dụng API',
                'excerpt' => 'Quy định về cấp API key, bảo mật, quota, webhook callback và các trường hợp giới hạn hoặc khóa API trên ApibankVN.',
                'seo_title' => 'Chính sách sử dụng API ApibankVN',
                'seo_description' => 'Quy định về API key, rate limit, webhook, quota theo gói và trách nhiệm bảo mật khi tích hợp API ApibankVN.',
                'content' => [
                    $this->heading('1. Cấp API key', 2),
                    $this->paragraph('API key được cấp cho người dùng có gói dịch vụ phù hợp và chỉ được sử dụng trong phạm vi quyền truy cập đã được hệ thống cho phép.'),
                    $this->heading('2. Bảo mật API key', 2),
                    $this->paragraph('Người dùng có trách nhiệm bảo mật API key, secret key và không chia sẻ cho bên thứ ba khi chưa có kiểm soát phù hợp. Mọi request phát sinh từ API key được xem là do người dùng thực hiện.'),
                    $this->heading('3. Rate limit và quota', 2),
                    $this->paragraph('Số lượng request, tốc độ gọi API và các giới hạn tích hợp phụ thuộc vào gói dịch vụ đang hoạt động. Hệ thống có thể từ chối hoặc giới hạn request vượt mức cho phép.'),
                    $this->heading('4. Webhook callback', 2),
                    $this->paragraph('Người dùng cần cấu hình webhook chính xác, đảm bảo endpoint nhận dữ liệu ổn định và tự xác minh sign hoặc secret theo tài liệu kỹ thuật do hệ thống cung cấp.'),
                    $this->heading('5. Log request và giám sát', 2),
                    $this->paragraph('Hệ thống có thể ghi nhận log request, response, thời gian gọi API và các metadata liên quan để phục vụ giám sát, vận hành, bảo mật và hỗ trợ đối soát.'),
                    $this->heading('6. Trường hợp bị khóa API', 2),
                    $this->list([
                        'Phát hiện hành vi bất thường, tấn công, spam request hoặc vượt giới hạn cho phép.',
                        'Lộ API key, secret key hoặc có dấu hiệu sử dụng trái phép.',
                        'Sử dụng API cho hành vi vi phạm pháp luật hoặc trái với điều khoản hệ thống.',
                    ]),
                    $this->heading('7. Cam kết vận hành', 2),
                    $this->paragraph('ApibankVN hướng tới duy trì dịch vụ ổn định, tuy nhiên không cam kết hoạt động liên tục 100% trong mọi thời điểm. Một số gián đoạn có thể xảy ra do bảo trì, lỗi ngân hàng, lỗi hạ tầng hoặc yếu tố bất khả kháng.'),
                    $this->heading('8. Khuyến nghị tích hợp', 2),
                    $this->list([
                        'Sử dụng IP whitelist cho API key nếu nghiệp vụ cho phép.',
                        'Xử lý retry, timeout và đối soát callback ở phía hệ thống tích hợp.',
                        'Không hardcode secret trong mã nguồn public hoặc client-side.',
                    ]),
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
