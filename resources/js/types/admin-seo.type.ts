export type SeoRobotsValue = 'index,follow' | 'noindex,follow';
export type SeoPostStatus = 'draft' | 'pending_review' | 'approved' | 'published' | 'rejected' | 'scheduled';

export interface AdminSeoOverviewSummary {
    total_categories: number;
    indexed_categories: number;
    total_posts: number;
    published_posts: number;
    sitemap_files: number;
    technical_score: number;
}

export interface AdminSeoSitemapEntry {
    title: string;
    path: string;
    description: string;
    included_count: string;
}

export interface AdminSeoCategoryItem {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    parent_id: number | null;
    seo_title: string | null;
    seo_description: string | null;
    robots: SeoRobotsValue;
    is_active: boolean;
    sort_order: number;
    posts_count?: number;
    children_count?: number;
    created_by_type: 'n8n' | 'admin';
    parent?: { id: number; name: string; slug: string } | null;
    updated_at: string;
}

export interface AdminSeoPostItem {
    id: number;
    seo_category_id: number | null;
    title: string;
    slug: string;
    excerpt: string | null;
    thumbnail: string | null;
    content: unknown[];
    faq: Array<{ question: string; answer: string }>;
    seo_title: string | null;
    seo_description: string | null;
    canonical_url: string | null;
    og_image: string | null;
    robots: SeoRobotsValue;
    focus_keyword: string | null;
    tags: string[];
    cover_alt: string | null;
    article_schema: boolean;
    breadcrumb_schema: boolean;
    status: SeoPostStatus;
    source_type: string | null;
    source_url: string | null;
    source_title: string | null;
    source_domain: string | null;
    external_id: string | null;
    content_hash: string | null;
    created_by_type: 'n8n' | 'admin';
    created_by_id: number | null;
    reviewed_by: number | null;
    reviewed_at: string | null;
    published_by: number | null;
    rejection_reason: string | null;
    index_status: 'index' | 'noindex';
    published_at: string | null;
    scheduled_at: string | null;
    created_at: string;
    updated_at: string;
    category?: {
        id: number;
        name: string;
    } | null;
    seo_tags?: AdminSeoTagItem[];
    sources?: AdminSeoPostSource[];
    activity_logs?: AdminSeoActivityLog[];
}

export interface AdminSeoTagItem {
    id: number;
    name: string;
    slug: string;
    created_by_type: 'n8n' | 'admin';
    is_active: boolean;
    posts_count?: number;
    updated_at: string;
}

export interface AdminSeoPostSource {
    id: number;
    title: string | null;
    url: string;
    domain: string | null;
    type: string | null;
}

export interface AdminSeoActivityLog {
    id: number;
    actor_type: string;
    actor_id: number | null;
    action: string;
    old_status: string | null;
    new_status: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
}

export interface AdminSeoCategoryPayload {
    name: string;
    slug: string;
    description?: string;
    parent_id?: number | null;
    seo_title?: string;
    seo_description?: string;
    robots: SeoRobotsValue;
    is_active?: boolean;
    sort_order?: number;
}

export interface AdminSeoPostPayload {
    seo_category_id?: number | null;
    title: string;
    slug: string;
    excerpt?: string;
    thumbnail?: string;
    content?: unknown[];
    faq?: Array<{ question: string; answer: string }>;
    seo_title?: string;
    seo_description?: string;
    canonical_url?: string;
    og_image?: string;
    robots: SeoRobotsValue;
    index_status?: 'index' | 'noindex';
    focus_keyword?: string;
    tags?: string[];
    cover_alt?: string;
    article_schema?: boolean;
    breadcrumb_schema?: boolean;
    status: SeoPostStatus;
    published_at?: string | null;
    scheduled_at?: string | null;
}

export interface AdminSeoTagPayload {
    name: string;
    slug: string;
    is_active?: boolean;
}
