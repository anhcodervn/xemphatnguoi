<?php

namespace App\Features\N8nContent\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;

class EditorContentNormalizerService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function normalize(mixed $content): array
    {
        if (is_array($content)) {
            return collect($content)
                ->map(fn (mixed $node): ?array => $this->sanitizeNode($node))
                ->filter()
                ->values()
                ->all();
        }

        if (! is_string($content) || trim($content) === '') {
            return [];
        }

        return $this->fromHtml($content);
    }

    /**
     * @param  list<array<string, mixed>>  $content
     */
    public function hash(array $content): string
    {
        return hash('sha256', (string) json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fromHtml(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousState = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="n8n-content-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        $root = $document->getElementById('n8n-content-root');

        if (! $root instanceof DOMElement) {
            return $this->plainTextNodes($html);
        }

        $nodes = [];

        foreach ($root->childNodes as $child) {
            $nodes = [...$nodes, ...$this->parseBlock($child)];
        }

        return $nodes !== [] ? $nodes : $this->plainTextNodes(strip_tags($html));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseBlock(DOMNode $node): array
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim((string) $node->textContent);

            return $text === '' ? [] : [['type' => 'paragraph', 'children' => [['text' => $text]]]];
        }

        if (! $node instanceof DOMElement) {
            return [];
        }

        $tag = mb_strtolower($node->tagName);

        if ($tag === 'p') {
            return [['type' => 'paragraph', 'children' => $this->parseInline($node)]];
        }

        if (preg_match('/^h([1-6])$/', $tag, $matches) === 1) {
            return [[
                'type' => 'heading',
                'level' => (int) $matches[1],
                'children' => $this->parseInline($node),
            ]];
        }

        if (in_array($tag, ['ul', 'ol'], true)) {
            $items = [];

            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement && mb_strtolower($child->tagName) === 'li') {
                    $items[] = $this->parseInline($child);
                }
            }

            return [['type' => 'list', 'ordered' => $tag === 'ol', 'items' => $items]];
        }

        if ($tag === 'img') {
            $src = $this->safeImageUrl($node->getAttribute('src'));

            return $src === null ? [] : [[
                'type' => 'image',
                'src' => $src,
                'alt' => trim(strip_tags($node->getAttribute('alt'))),
            ]];
        }

        $children = [];

        foreach ($node->childNodes as $child) {
            $children = [...$children, ...$this->parseBlock($child)];
        }

        if (in_array($tag, ['article', 'div', 'section'], true) && $children !== []) {
            return [['type' => 'container', 'tag' => $tag, 'children' => $children]];
        }

        return $children;
    }

    /**
     * @param  array<string, mixed>  $style
     * @return list<array<string, mixed>>
     */
    private function parseInline(DOMNode $node, array $style = []): array
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = (string) $node->textContent;

            return trim($text) === '' ? [] : [['text' => $text, ...$style]];
        }

        if (! $node instanceof DOMElement) {
            return [];
        }

        $tag = mb_strtolower($node->tagName);
        $nextStyle = $style;
        $nextStyle['bold'] = ($nextStyle['bold'] ?? false) || in_array($tag, ['b', 'strong'], true);
        $nextStyle['italic'] = ($nextStyle['italic'] ?? false) || in_array($tag, ['em', 'i'], true);
        $nextStyle['underline'] = ($nextStyle['underline'] ?? false) || $tag === 'u';
        $nextStyle['strike'] = ($nextStyle['strike'] ?? false) || in_array($tag, ['s', 'strike'], true);
        $nextStyle = array_filter($nextStyle, fn (mixed $value): bool => $value === true);

        if ($tag === 'br') {
            return [['text' => "\n", ...$nextStyle]];
        }

        $children = [];

        foreach ($node->childNodes as $child) {
            $children = [...$children, ...$this->parseInline($child, $nextStyle)];
        }

        return $children;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function plainTextNodes(string $content): array
    {
        return collect(preg_split('/\R{2,}/u', trim(strip_tags($content))) ?: [])
            ->map(fn (string $paragraph): array => [
                'type' => 'paragraph',
                'children' => [['text' => trim($paragraph)]],
            ])
            ->filter(fn (array $node): bool => $node['children'][0]['text'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function sanitizeNode(mixed $node): ?array
    {
        if (! is_array($node)) {
            return null;
        }

        $type = $node['type'] ?? null;

        if ($type === 'image') {
            $src = $this->safeImageUrl($node['src'] ?? null);

            return $src === null ? null : [
                'type' => 'image',
                'src' => $src,
                'alt' => trim(strip_tags((string) ($node['alt'] ?? ''))),
            ];
        }

        if ($type === 'list') {
            return [
                'type' => 'list',
                'ordered' => (bool) ($node['ordered'] ?? false),
                'items' => collect(is_array($node['items'] ?? null) ? $node['items'] : [])
                    ->map(fn (mixed $item): array => $this->sanitizeInline(is_array($item) ? $item : []))
                    ->values()
                    ->all(),
            ];
        }

        if ($type === 'container') {
            $children = collect(is_array($node['children'] ?? null) ? $node['children'] : [])
                ->map(fn (mixed $child): ?array => $this->sanitizeNode($child))
                ->filter()
                ->values()
                ->all();

            return $children === [] ? null : [
                'type' => 'container',
                'tag' => in_array($node['tag'] ?? null, ['article', 'div', 'section'], true) ? $node['tag'] : 'div',
                'children' => $children,
            ];
        }

        if (in_array($type, ['paragraph', 'heading'], true)) {
            $result = [
                'type' => $type,
                'children' => $this->sanitizeInline(is_array($node['children'] ?? null) ? $node['children'] : []),
            ];

            if ($type === 'heading') {
                $result['level'] = max(1, min(6, (int) ($node['level'] ?? 2)));
            }

            return $result;
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $children
     * @return list<array<string, mixed>>
     */
    private function sanitizeInline(array $children): array
    {
        return collect($children)
            ->filter(fn (mixed $child): bool => is_array($child) && array_key_exists('text', $child))
            ->map(function (array $child): array {
                return array_filter([
                    'text' => strip_tags((string) $child['text']),
                    'bold' => ! empty($child['bold']) ?: null,
                    'italic' => ! empty($child['italic']) ?: null,
                    'underline' => ! empty($child['underline']) ?: null,
                    'strike' => ! empty($child['strike']) ?: null,
                ], fn (mixed $value): bool => $value !== null);
            })
            ->values()
            ->all();
    }

    private function safeImageUrl(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $url = trim($value);

        return Str::startsWith($url, ['https://', 'http://', '/']) ? $url : null;
    }
}
