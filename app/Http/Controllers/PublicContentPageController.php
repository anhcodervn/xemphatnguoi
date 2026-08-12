<?php

namespace App\Http\Controllers;

use App\Support\EditorContentRenderer;
use App\Support\SettingStore;
use Illuminate\Contracts\View\View;

class PublicContentPageController extends Controller
{
    public function __construct(
        protected EditorContentRenderer $contentRenderer,
    ) {}

    /**
     * @return array<string, array<string, string>>
     */
    protected function pageMap(): array
    {
        return [
            'gioi-thieu' => [
                'fallback_title' => 'Giới thiệu',
                'fallback_description' => 'Thông tin tổng quan về DailyProxy.vn, mô hình dịch vụ proxy API và nhóm người dùng phù hợp.',
                'title_key' => 'about_page_title',
                'excerpt_key' => 'about_page_excerpt',
                'content_key' => 'about_page_content',
                'seo_title_key' => 'about_page_seo_title',
                'seo_description_key' => 'about_page_seo_description',
                'published_key' => 'about_page_is_published',
            ],
            'lien-he' => [
                'fallback_title' => 'Liên hệ',
                'fallback_description' => 'Thông tin hỗ trợ, cách gửi yêu cầu và các kênh kết nối với đội ngũ vận hành DailyProxy.vn.',
                'title_key' => 'contact_page_title',
                'excerpt_key' => 'contact_page_excerpt',
                'content_key' => 'contact_page_content',
                'seo_title_key' => 'contact_page_seo_title',
                'seo_description_key' => 'contact_page_seo_description',
                'published_key' => 'contact_page_is_published',
            ],
            'dieu-khoan-su-dung' => [
                'fallback_title' => 'Điều khoản sử dụng',
                'fallback_description' => 'Các quy định khi sử dụng dịch vụ DailyProxy.vn, trách nhiệm người dùng và giới hạn hệ thống.',
                'title_key' => 'terms_page_title',
                'excerpt_key' => 'terms_page_excerpt',
                'content_key' => 'terms_page_content',
                'seo_title_key' => 'terms_page_seo_title',
                'seo_description_key' => 'terms_page_seo_description',
                'published_key' => 'terms_page_is_published',
            ],
            'chinh-sach-bao-mat' => [
                'fallback_title' => 'Chính sách bảo mật',
                'fallback_description' => 'Cách DailyProxy.vn thu thập, lưu trữ, bảo vệ và xử lý dữ liệu của người dùng.',
                'title_key' => 'privacy_page_title',
                'excerpt_key' => 'privacy_page_excerpt',
                'content_key' => 'privacy_page_content',
                'seo_title_key' => 'privacy_page_seo_title',
                'seo_description_key' => 'privacy_page_seo_description',
                'published_key' => 'privacy_page_is_published',
            ],
            'chinh-sach-hoan-tien' => [
                'fallback_title' => 'Chính sách hoàn tiền',
                'fallback_description' => 'Điều kiện hoàn tiền, thời gian xử lý và các trường hợp không áp dụng hoàn tiền.',
                'title_key' => 'refund_policy_title',
                'excerpt_key' => 'refund_policy_excerpt',
                'content_key' => 'refund_policy_content',
                'seo_title_key' => 'refund_policy_seo_title',
                'seo_description_key' => 'refund_policy_seo_description',
                'published_key' => 'refund_policy_is_published',
            ],
            'chinh-sach-thanh-toan' => [
                'fallback_title' => 'Chính sách thanh toán',
                'fallback_description' => 'Quy định về phương thức thanh toán, kích hoạt đơn hàng và đối soát giao dịch.',
                'title_key' => 'payment_policy_title',
                'excerpt_key' => 'payment_policy_excerpt',
                'content_key' => 'payment_policy_content',
                'seo_title_key' => 'payment_policy_seo_title',
                'seo_description_key' => 'payment_policy_seo_description',
                'published_key' => 'payment_policy_is_published',
            ],
            'chinh-sach-su-dung-api' => [
                'fallback_title' => 'Chính sách sử dụng dịch vụ',
                'fallback_description' => 'Quy định về quota API, callback và các giới hạn sử dụng trên DailyProxy.vn.',
                'title_key' => 'api_usage_policy_title',
                'excerpt_key' => 'api_usage_policy_excerpt',
                'content_key' => 'api_usage_policy_content',
                'seo_title_key' => 'api_usage_policy_seo_title',
                'seo_description_key' => 'api_usage_policy_seo_description',
                'published_key' => 'api_usage_policy_is_published',
            ],
            'mien-tru-trach-nhiem' => [
                'fallback_title' => 'Miễn trừ trách nhiệm',
                'fallback_description' => 'Các giới hạn trách nhiệm của DailyProxy.vn khi cung cấp dịch vụ proxy qua API và hạ tầng kỹ thuật của hệ thống.',
                'title_key' => 'disclaimer_title',
                'excerpt_key' => 'disclaimer_excerpt',
                'content_key' => 'disclaimer_content',
                'seo_title_key' => 'disclaimer_seo_title',
                'seo_description_key' => 'disclaimer_seo_description',
                'published_key' => 'disclaimer_is_published',
            ],
            'cau-hoi-thuong-gap' => [
                'fallback_title' => 'Câu hỏi thường gặp',
                'fallback_description' => 'Những câu hỏi phổ biến về dịch vụ proxy, API, logs và vận hành hệ thống.',
                'title_key' => 'faq_page_title',
                'excerpt_key' => 'faq_page_excerpt',
                'content_key' => 'faq_page_content',
                'seo_title_key' => 'faq_page_seo_title',
                'seo_description_key' => 'faq_page_seo_description',
                'published_key' => 'faq_page_is_published',
            ],
            'trang-thai-he-thong' => [
                'fallback_title' => 'Trạng thái hệ thống',
                'fallback_description' => 'Thông tin vận hành, độ ổn định dịch vụ và các thông báo gián đoạn nếu có.',
                'title_key' => 'system_status_title',
                'excerpt_key' => 'system_status_excerpt',
                'content_key' => 'system_status_content',
                'seo_title_key' => 'system_status_seo_title',
                'seo_description_key' => 'system_status_seo_description',
                'published_key' => 'system_status_is_published',
            ],
            'cap-nhat-he-thong' => [
                'fallback_title' => 'Cập nhật hệ thống',
                'fallback_description' => 'Lịch sử cập nhật, thay đổi tính năng, tối ưu hiệu năng và thông báo bảo trì.',
                'title_key' => 'system_updates_title',
                'excerpt_key' => 'system_updates_excerpt',
                'content_key' => 'system_updates_content',
                'seo_title_key' => 'system_updates_seo_title',
                'seo_description_key' => 'system_updates_seo_description',
                'published_key' => 'system_updates_is_published',
            ],
        ];
    }

    public function show(string $slug, SettingStore $settingStore): View
    {
        $page = $this->pageMap()[$slug] ?? abort(404);

        $systemSettings = $settingStore->getMany([
            'site_name' => config('app.name', 'DailyProxy.vn'),
            'site_domain' => '',
            'site_description' => '',
            'support_email' => '',
            'hotline' => '',
            'address' => '',
            'facebook' => '',
            'zalo' => '',
            'youtube' => '',
            'meta_title' => '',
            'meta_description' => '',
            'light_logo' => '',
            'dark_logo' => '',
            'favicon' => '',
            'og_image' => '',
            'gtm_id' => '',
            'meta_pixel_id' => '',
            $page['title_key'] => $page['fallback_title'],
            $page['excerpt_key'] => $page['fallback_description'],
            $page['content_key'] => [],
            $page['seo_title_key'] => '',
            $page['seo_description_key'] => '',
            $page['published_key'] => true,
        ]);

        abort_if(! (bool) ($systemSettings[$page['published_key']] ?? true), 404);

        $pageTitle = (string) ($systemSettings[$page['title_key']] ?: $page['fallback_title']);
        $pageDescription = (string) ($systemSettings[$page['excerpt_key']] ?: $page['fallback_description']);
        $pageMetaTitle = (string) ($systemSettings[$page['seo_title_key']] ?: $pageTitle.' | '.($systemSettings['site_name'] ?: config('app.name', 'DailyProxy.vn')));
        $pageMetaDescription = (string) ($systemSettings[$page['seo_description_key']] ?: $pageDescription);
        $content = is_array($systemSettings[$page['content_key']] ?? null) ? $systemSettings[$page['content_key']] : [];

        return view('pages.content.show', [
            'systemSettings' => $systemSettings,
            'pageSlug' => $slug,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'pageMetaTitle' => $pageMetaTitle,
            'pageMetaDescription' => $pageMetaDescription,
            'contentBlocks' => $content,
            'contentHtml' => $this->contentRenderer->renderNodes($content),
            'contentLinks' => collect($this->pageMap())
                ->map(fn (array $item) => ['title' => $item['fallback_title']])
                ->all(),
        ]);
    }
}
