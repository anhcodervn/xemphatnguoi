<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EditorContentRenderer
{
    /**
     * @param  array<int, mixed>  $nodes
     */
    public function renderNodes(array $nodes): HtmlString
    {
        $html = collect($nodes)
            ->map(fn (mixed $node) => $this->renderNode($node))
            ->implode('');

        return new HtmlString($html);
    }

    /**
     * @param  array<int, mixed>  $nodes
     */
    public function extractText(array $nodes): string
    {
        return trim(
            preg_replace('/\s+/u', ' ', strip_tags($this->renderNodes($nodes)->toHtml())) ?? ''
        );
    }

    /**
     * @param  array<int, mixed>  $nodes
     */
    public function estimateReadingMinutes(array $nodes, int $wordsPerMinute = 220): int
    {
        $plainText = $this->extractText($nodes);
        $wordCount = max(1, str_word_count(Str::ascii($plainText)));

        return max(1, (int) ceil($wordCount / max(1, $wordsPerMinute)));
    }

    /**
     * @param  array<int, mixed>  $nodes
     */
    public function firstImage(array $nodes): ?string
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (($node['type'] ?? null) === 'image' && filled($node['src'] ?? null)) {
                $src = $this->safeImageUrl($node['src']);

                if ($src !== null) {
                    return $src;
                }
            }

            if (($node['type'] ?? null) === 'container' && is_array($node['children'] ?? null)) {
                $src = $this->firstImage($node['children']);

                if ($src !== null) {
                    return $src;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $nodes
     * @return array<int, array{id: string, level: int, text: string}>
     */
    public function headingIndex(array $nodes): array
    {
        $headings = [];

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            if (($node['type'] ?? null) === 'heading') {
                $text = $this->extractInlineText(is_array($node['children'] ?? null) ? $node['children'] : []);

                if ($text !== '') {
                    $headings[] = [
                        'id' => Str::slug($text),
                        'level' => max(1, min((int) ($node['level'] ?? 2), 6)),
                        'text' => $text,
                    ];
                }
            }

            if (($node['type'] ?? null) === 'container' && is_array($node['children'] ?? null)) {
                $headings = [...$headings, ...$this->headingIndex($node['children'])];
            }
        }

        return $headings;
    }

    protected function renderNode(mixed $node): string
    {
        if (! is_array($node)) {
            return '';
        }

        if (($node['type'] ?? null) === 'container' && is_array($node['children'] ?? null)) {
            $tag = in_array($node['tag'] ?? 'div', ['div', 'section'], true) ? (string) $node['tag'] : 'div';

            return sprintf(
                '<%1$s>%2$s</%1$s>',
                $tag,
                collect($node['children'])->map(fn (mixed $child) => $this->renderNode($child))->implode(''),
            );
        }

        return $this->renderBlock($node);
    }

    /**
     * @param  array<string, mixed>  $block
     */
    protected function renderBlock(array $block): string
    {
        return match ($block['type'] ?? '') {
            'heading' => $this->renderHeading($block),
            'paragraph' => '<p>'.$this->renderInline($block['children'] ?? []).'</p>',
            'image' => $this->renderImage($block),
            'list' => $this->renderList($block),
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $block
     */
    protected function renderHeading(array $block): string
    {
        $level = (int) ($block['level'] ?? 2);
        $safeLevel = max(1, min($level, 6));
        $headingText = $this->extractInlineText(is_array($block['children'] ?? null) ? $block['children'] : []);
        $idAttribute = $headingText !== '' ? ' id="'.e(Str::slug($headingText)).'"' : '';

        return sprintf(
            '<h%1$d%3$s>%2$s</h%1$d>',
            $safeLevel,
            $this->renderInline($block['children'] ?? []),
            $idAttribute,
        );
    }

    /**
     * @param  array<string, mixed>  $block
     */
    protected function renderImage(array $block): string
    {
        $rawSrc = $this->safeImageUrl($block['src'] ?? null);
        $src = $rawSrc !== null ? e($rawSrc) : '';
        $alt = isset($block['alt']) ? e((string) $block['alt']) : '';

        if ($src === '') {
            return '';
        }

        return sprintf(
            '<img src="%s" alt="%s" class="my-6 rounded-[14px] border border-slate-200 bg-white shadow-sm" />',
            $src,
            $alt,
        );
    }

    /**
     * @param  array<string, mixed>  $block
     */
    protected function renderList(array $block): string
    {
        $tag = ! empty($block['ordered']) ? 'ol' : 'ul';
        $items = is_array($block['items'] ?? null) ? $block['items'] : [];

        return sprintf(
            '<%1$s>%2$s</%1$s>',
            $tag,
            collect($items)
                ->map(fn (mixed $item) => '<li>'.$this->renderInline(is_array($item) ? $item : []).'</li>')
                ->implode(''),
        );
    }

    /**
     * @param  array<int, mixed>  $children
     */
    protected function renderInline(array $children): string
    {
        return collect($children)
            ->map(function (mixed $child): string {
                if (! is_array($child)) {
                    return '';
                }

                $text = e((string) ($child['text'] ?? ''));

                if (! empty($child['bold'])) {
                    $text = '<strong>'.$text.'</strong>';
                }

                if (! empty($child['italic'])) {
                    $text = '<em>'.$text.'</em>';
                }

                if (! empty($child['underline'])) {
                    $text = '<u>'.$text.'</u>';
                }

                if (! empty($child['strike'])) {
                    $text = '<s>'.$text.'</s>';
                }

                $styles = [];

                if ($color = $this->safeCssColor($child['color'] ?? null)) {
                    $styles[] = 'color:'.e($color);
                }

                if ($background = $this->safeCssColor($child['background'] ?? null)) {
                    $styles[] = 'background-color:'.e($background);
                }

                if ($styles !== []) {
                    return '<span style="'.implode(';', $styles).'">'.$text.'</span>';
                }

                return $text;
            })
            ->implode('');
    }

    private function safeCssColor(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $color = trim($value);
        $pattern = '/^(?:#[0-9a-f]{3,8}|rgba?\([\d\s.,%]+\)|hsla?\([\d\s.,%]+\)|[a-z]{3,20})$/i';

        return preg_match($pattern, $color) === 1 ? $color : null;
    }

    private function safeImageUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $url = trim($value);

        return Str::startsWith($url, ['https://', 'http://', '/']) ? $url : null;
    }

    /**
     * @param  array<int, mixed>  $children
     */
    protected function extractInlineText(array $children): string
    {
        return trim(
            collect($children)
                ->map(fn (mixed $child) => is_array($child) ? (string) ($child['text'] ?? '') : '')
                ->implode('')
        );
    }
}
