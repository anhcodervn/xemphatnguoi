import { BookOpenText, FolderTree, Map, ShieldCheck, type LucideIcon } from "lucide-vue-next";

export type SeoReference = {
    label: string;
    url: string;
    summary: string;
};

export type SeoMetricCard = {
    label: string;
    value: string;
    description: string;
    tone: "blue" | "emerald" | "violet" | "amber";
    icon: LucideIcon;
};

export type SeoCategoryItem = {
    id: number;
    name: string;
    slug: string;
    seoTitle: string;
    seoDescription: string;
    robots: "index,follow" | "noindex,follow";
    articleCount: number;
    updatedAt: string;
};

export type SeoPostItem = {
    id: number;
    title: string;
    slug: string;
    category: string;
    status: "draft" | "published" | "scheduled";
    seoScore: number;
    canonicalUrl: string;
    updatedAt: string;
};

export type SitemapEntry = {
    title: string;
    path: string;
    description: string;
    includedCount: string;
};

export const seoReferences: SeoReference[] = [
    {
        label: "SEO Starter Guide",
        url: "https://developers.google.com/search/docs/fundamentals/seo-starter-guide",
        summary: "Google khuyến nghị nội dung phải hữu ích, rõ cấu trúc, có title và description riêng cho từng trang.",
    },
    {
        label: "Sitemaps",
        url: "https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview",
        summary: "Sitemap nên liệt kê URL quan trọng và được gửi lại khi có thay đổi lớn về nội dung.",
    },
    {
        label: "Article structured data",
        url: "https://developers.google.com/search/docs/appearance/structured-data/article",
        summary: "Bài viết nên có schema Article hoặc BlogPosting với title, image, date và author rõ ràng.",
    },
    {
        label: "Breadcrumb structured data",
        url: "https://developers.google.com/search/docs/appearance/structured-data/breadcrumb",
        summary: "Breadcrumb giúp máy tìm kiếm hiểu ngữ cảnh phân cấp nội dung và cải thiện điều hướng.",
    },
    {
        label: "Canonical URL",
        url: "https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls",
        summary: "Canonical cần trỏ đúng bản chính để tránh trùng lặp URL và chia tách tín hiệu xếp hạng.",
    },
];

export const seoMetricCards: SeoMetricCard[] = [
    {
        label: "Danh mục index",
        value: "12",
        description: "Các nhóm nội dung đang được cấu hình title, robots và canonical.",
        tone: "blue",
        icon: FolderTree,
    },
    {
        label: "Bài viết SEO",
        value: "38",
        description: "Bài viết có slug sạch, meta description và cấu trúc heading rõ ràng.",
        tone: "emerald",
        icon: BookOpenText,
    },
    {
        label: "Sitemap khả dụng",
        value: "4",
        description: "Sitemap index, bài viết, danh mục và landing đều có thể submit Search Console.",
        tone: "violet",
        icon: Map,
    },
    {
        label: "Checklist kỹ thuật",
        value: "8/10",
        description: "Ưu tiên canonical, robots meta, breadcrumb và schema bài viết.",
        tone: "amber",
        icon: ShieldCheck,
    },
];

export const seoCategories: SeoCategoryItem[] = [
    {
        id: 1,
        name: "Hướng dẫn API captcha",
        slug: "huong-dan-api-captcha",
        seoTitle: "Hướng dẫn API giải captcha | GiaiCaptcha",
        seoDescription: "Nhóm bài viết hướng dẫn tạo task captcha, cấu hình payload và xử lý kết quả trả về.",
        robots: "index,follow",
        articleCount: 14,
        updatedAt: "2026-06-01 09:30",
    },
    {
        id: 2,
        name: "Vận hành hệ thống",
        slug: "van-hanh-he-thong",
        seoTitle: "Vận hành queue và task captcha | GiaiCaptcha",
        seoDescription: "Quản trị queue, task captcha, cảnh báo và theo dõi trạng thái dịch vụ.",
        robots: "index,follow",
        articleCount: 11,
        updatedAt: "2026-06-01 14:10",
    },
    {
        id: 3,
        name: "Chính sách & pháp lý",
        slug: "chinh-sach-phap-ly",
        seoTitle: "Chính sách sử dụng dịch vụ | GiaiCaptcha",
        seoDescription: "Các nội dung điều khoản, bảo mật, hoàn tiền và miễn trừ trách nhiệm.",
        robots: "index,follow",
        articleCount: 8,
        updatedAt: "2026-05-31 18:45",
    },
];

export const seoPosts: SeoPostItem[] = [
    {
        id: 1,
        title: "Hướng dẫn tạo task captcha đầu tiên trên GiaiCaptcha",
        slug: "huong-dan-tao-task-captcha-dau-tien-tren-giapcaptcha",
        category: "Hướng dẫn API captcha",
        status: "published",
        seoScore: 92,
        canonicalUrl: "https://giapcaptcha.vn/blog/huong-dan-tao-task-captcha-dau-tien-tren-giapcaptcha",
        updatedAt: "2026-06-01 11:20",
    },
    {
        id: 2,
        title: "Checklist queue và retry an toàn cho task captcha",
        slug: "checklist-queue-va-retry-an-toan-cho-task-captcha",
        category: "Vận hành hệ thống",
        status: "published",
        seoScore: 88,
        canonicalUrl: "https://giapcaptcha.vn/blog/checklist-queue-va-retry-an-toan-cho-task-captcha",
        updatedAt: "2026-05-31 16:00",
    },
    {
        id: 3,
        title: "Khi nào nên dùng noindex cho trang nội bộ",
        slug: "khi-nao-nen-dung-noindex-cho-trang-noi-bo",
        category: "Chính sách & pháp lý",
        status: "draft",
        seoScore: 74,
        canonicalUrl: "https://giapcaptcha.vn/blog/khi-nao-nen-dung-noindex-cho-trang-noi-bo",
        updatedAt: "2026-05-30 10:15",
    },
];

export const sitemapEntries: SitemapEntry[] = [
    {
        title: "Sitemap index",
        path: "/sitemap.xml",
        description: "Tệp tổng hợp để submit trong Search Console và khai báo các sitemap con.",
        includedCount: "4 file",
    },
    {
        title: "Sitemap bài viết",
        path: "/sitemap-posts.xml",
        description: "Chỉ chứa các bài viết public đã publish và có canonical hợp lệ.",
        includedCount: "38 URL",
    },
    {
        title: "Sitemap danh mục",
        path: "/sitemap-categories.xml",
        description: "Tập trung các trang category index/follow để gom chủ đề nội dung.",
        includedCount: "12 URL",
    },
    {
        title: "Sitemap landing & pháp lý",
        path: "/sitemap-pages.xml",
        description: "Bao gồm landing, docs public, điều khoản và các trang nội dung tĩnh.",
        includedCount: "18 URL",
    },
];

export const seoTechnicalChecklist = [
    "Mỗi bài viết cần title riêng, không nhồi từ khóa và dài khoảng 50-60 ký tự.",
    "Meta description nên ngắn gọn, duy nhất và mô tả đúng ý định tìm kiếm.",
    "Slug phải dễ đọc, dùng dấu gạch nối và tránh tham số thừa trong URL.",
    "Các bài viết chính nên có canonical tự trỏ về đúng URL public của bài.",
    "Danh mục và bài viết quan trọng nên nằm trong sitemap và không bị robots noindex.",
    "Bài viết nên có breadcrumb rõ ngữ cảnh danh mục thay vì chỉ mirror theo URL.",
    "Bài blog nên gắn Article hoặc BlogPosting schema với image, author, published_at và modified_at.",
    "Ảnh bài viết nên có alt text rõ nghĩa và nằm gần phần nội dung liên quan.",
];

export const seoToneClasses: Record<SeoMetricCard["tone"], string> = {
    blue: "border-sky-100 bg-sky-50 text-sky-700",
    emerald: "border-emerald-100 bg-emerald-50 text-emerald-700",
    violet: "border-violet-100 bg-violet-50 text-violet-700",
    amber: "border-amber-100 bg-amber-50 text-amber-700",
};

export const seoToneIconClasses: Record<SeoMetricCard["tone"], string> = {
    blue: "from-sky-500 to-blue-600",
    emerald: "from-emerald-500 to-teal-600",
    violet: "from-violet-500 to-indigo-600",
    amber: "from-amber-500 to-orange-500",
};
