<?php

use App\Features\N8nContent\Services\EditorContentNormalizerService;
use App\Support\EditorContentRenderer;

it('renders safe editor links with secure external target attributes', function () {
    $html = app(EditorContentRenderer::class)->renderNodes([
        [
            'type' => 'paragraph',
            'children' => [
                [
                    'text' => 'Trang hợp lệ',
                    'href' => 'https://example.com/path?a=1&b=2',
                    'target' => '_blank',
                    'title' => 'Xem "chi tiết"',
                ],
            ],
        ],
    ])->toHtml();

    expect($html)
        ->toContain('href="https://example.com/path?a=1&amp;b=2"')
        ->toContain('target="_blank" rel="noopener noreferrer"')
        ->toContain('title="Xem &quot;chi tiết&quot;"')
        ->toContain('>Trang hợp lệ</a>');
});

it('does not render unsafe editor link protocols', function () {
    $html = app(EditorContentRenderer::class)->renderNodes([
        [
            'type' => 'paragraph',
            'children' => [
                ['text' => 'Không an toàn', 'href' => 'javascript:alert(1)'],
                ['text' => 'Data URL', 'href' => 'data:text/html,bad'],
            ],
        ],
    ])->toHtml();

    expect($html)
        ->toBe('<p>Không an toànData URL</p>')
        ->not->toContain('<a', 'javascript:', 'data:');
});

it('normalizes links from html without losing inline spacing', function () {
    $content = app(EditorContentNormalizerService::class)->normalize(
        '<p><strong>Một</strong> <a href="https://example.com" target="_blank" title="Chi tiết">liên kết</a></p>',
    );

    expect($content[0]['children'])->toBe([
        ['text' => 'Một', 'bold' => true],
        ['text' => ' '],
        [
            'text' => 'liên kết',
            'href' => 'https://example.com',
            'target' => '_blank',
            'title' => 'Chi tiết',
        ],
    ]);
});

it('sanitizes links supplied as editor nodes', function () {
    $content = app(EditorContentNormalizerService::class)->normalize([
        [
            'type' => 'paragraph',
            'children' => [
                ['text' => 'Email', 'href' => 'mailto:hello@example.com'],
                ['text' => 'Xấu', 'href' => 'javascript:alert(1)', 'target' => '_blank'],
            ],
        ],
    ]);

    expect($content[0]['children'])->toBe([
        ['text' => 'Email', 'href' => 'mailto:hello@example.com'],
        ['text' => 'Xấu'],
    ]);
});
