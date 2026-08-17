export type SeoReference = {
    label: string;
    url: string;
    summary: string;
};

export type SeoMetricTone = 'blue' | 'emerald' | 'violet' | 'amber';

export const seoReferences: SeoReference[] = [
    {
        label: 'SEO Starter Guide',
        url: 'https://developers.google.com/search/docs/fundamentals/seo-starter-guide',
        summary: 'Google khuyến nghị nội dung hữu ích, rõ cấu trúc, có title và description riêng cho từng trang.',
    },
    {
        label: 'Sitemaps',
        url: 'https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview',
        summary: 'Sitemap nên chỉ liệt kê URL canonical quan trọng và được cập nhật khi nội dung thay đổi.',
    },
    {
        label: 'Article structured data',
        url: 'https://developers.google.com/search/docs/appearance/structured-data/article',
        summary: 'Bài viết nên có schema Article với tiêu đề, ảnh, ngày xuất bản và tác giả rõ ràng.',
    },
    {
        label: 'Breadcrumb structured data',
        url: 'https://developers.google.com/search/docs/appearance/structured-data/breadcrumb',
        summary: 'Breadcrumb giúp máy tìm kiếm hiểu cấu trúc nội dung và cải thiện điều hướng.',
    },
    {
        label: 'Canonical URL',
        url: 'https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls',
        summary: 'Canonical cần trỏ đúng bản chính để tránh URL trùng lặp và phân tán tín hiệu xếp hạng.',
    },
];

export const seoTechnicalChecklist = [
    'Mỗi bài viết cần title riêng, không nhồi từ khóa và mô tả đúng nội dung.',
    'Meta description nên ngắn gọn, duy nhất và phù hợp ý định tìm kiếm.',
    'Slug phải dễ đọc, dùng dấu gạch nối và tránh tham số thừa.',
    'Bài viết chính nên có canonical tự trỏ về URL public tương ứng.',
    'Chỉ URL public quan trọng mới nằm trong sitemap và được đặt index,follow.',
    'Trang kết quả biển số phải giữ noindex,follow và không xuất hiện trong sitemap.',
    'Bài blog nên có Article schema với ảnh, tác giả, published_at và modified_at.',
    'FAQPage chỉ được xuất khi trang thật sự hiển thị câu hỏi và câu trả lời tương ứng.',
];

export const seoToneClasses: Record<SeoMetricTone, string> = {
    blue: 'border-sky-100 bg-sky-50 text-sky-700',
    emerald: 'border-emerald-100 bg-emerald-50 text-emerald-700',
    violet: 'border-violet-100 bg-violet-50 text-violet-700',
    amber: 'border-amber-100 bg-amber-50 text-amber-700',
};

export const seoToneIconClasses: Record<SeoMetricTone, string> = {
    blue: 'from-sky-500 to-blue-600',
    emerald: 'from-emerald-500 to-teal-600',
    violet: 'from-violet-500 to-indigo-600',
    amber: 'from-amber-500 to-orange-500',
};
