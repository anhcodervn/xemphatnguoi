<?php

it('provides SEO thumbnail and Open Graph upload controls', function () {
    $page = file_get_contents(dirname(__DIR__, 2).'/resources/js/pages/admin/seo/posts/create/index.vue');

    expect($page)
        ->toContain("openImageUpload('thumbnail')")
        ->toContain("openImageUpload('og_image')")
        ->toContain('ImageUploadModal')
        ->toContain('Ảnh Open Graph / background chia sẻ')
        ->toContain('border border-slate-300 text-sm');
});

it('supports select drag drop and clipboard image uploads converted to WebP', function () {
    $modal = file_get_contents(dirname(__DIR__, 2).'/resources/js/components/shared/ImageUploadModal/index.vue');

    expect($modal)
        ->toContain("window.addEventListener('paste', handlePaste)")
        ->toContain('@drop.prevent="handleDrop"')
        ->toContain('fileInput.value?.click()')
        ->toContain("canvas.toBlob((result) => (result ? resolve(result) : reject(new Error('WebP conversion failed.'))), 'image/webp', 0.82)")
        ->toContain("formData.append('image', optimizedFile.value)")
        ->toContain("api.post('/api/uploads/image'")
        ->toContain("new File([blob], webpName, { type: 'image/webp'");
});
