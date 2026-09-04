<?php

it('shows visible borders and focus states on every article filter control', function () {
    $page = file_get_contents(dirname(__DIR__, 2).'/resources/js/pages/admin/seo/posts/index.vue');

    expect($page)
        ->toContain('v-model="filters.search"')
        ->toContain('v-model="filters.status"')
        ->toContain('v-model="filters.category_id"')
        ->toContain('v-model="filters.created_by_type"')
        ->toContain('v-model="filters.date"')
        ->toContain('v-model="filters.source"')
        ->and(substr_count($page, 'border border-slate-300'))
        ->toBeGreaterThanOrEqual(6)
        ->and(substr_count($page, 'focus:border-violet-400'))
        ->toBeGreaterThanOrEqual(5);
});
