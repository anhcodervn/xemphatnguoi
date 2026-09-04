<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class RichTextSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'em', 'h2', 'h3', 'h4', 'i', 'img',
        'li', 'ol', 'p', 's', 'span', 'strike', 'strong', 'u', 'ul',
    ];

    private const REMOVE_WITH_CONTENT = [
        'embed', 'form', 'iframe', 'math', 'object', 'script', 'style', 'svg', 'template',
    ];

    public function sanitize(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div data-rich-text-root="1">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        $root = $document->getElementsByTagName('div')->item(0);

        if (! $root instanceof DOMElement) {
            return null;
        }

        foreach (iterator_to_array($root->childNodes) as $node) {
            $this->sanitizeNode($node);
        }

        $sanitized = collect(iterator_to_array($root->childNodes))
            ->map(fn (DOMNode $node): string => $document->saveHTML($node) ?: '')
            ->implode('');

        return blank(strip_tags($sanitized)) && ! str_contains($sanitized, '<img') ? null : trim($sanitized);
    }

    private function sanitizeNode(DOMNode $node): void
    {
        if ($node->nodeType === XML_COMMENT_NODE) {
            $node->parentNode?->removeChild($node);

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
            $node->parentNode?->removeChild($node);

            return;
        }

        foreach (iterator_to_array($node->childNodes) as $childNode) {
            $this->sanitizeNode($childNode);
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            $this->unwrap($node);

            return;
        }

        $this->sanitizeAttributes($node, $tag);
    }

    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowedAttributes = match ($tag) {
            'a' => ['href', 'target', 'title'],
            'img' => ['alt', 'height', 'src', 'title', 'width'],
            'p', 'span', 'h2', 'h3', 'h4', 'blockquote' => ['style'],
            default => [],
        };

        foreach (iterator_to_array($element->attributes) as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowedAttributes, true)) {
                $element->removeAttributeNode($attribute);
            }
        }

        if ($element->hasAttribute('style')) {
            $style = $this->sanitizeStyle($element->getAttribute('style'));
            $style === null ? $element->removeAttribute('style') : $element->setAttribute('style', $style);
        }

        if ($tag === 'a') {
            $this->sanitizeLink($element);
        }

        if ($tag === 'img') {
            if (! $this->isSafeUrl($element->getAttribute('src'), false)) {
                $element->parentNode?->removeChild($element);

                return;
            }

            foreach (['height', 'width'] as $dimension) {
                $value = $element->getAttribute($dimension);

                if ($value !== '' && (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value > 2000)) {
                    $element->removeAttribute($dimension);
                }
            }
        }
    }

    private function sanitizeLink(DOMElement $element): void
    {
        if (! $this->isSafeUrl($element->getAttribute('href'), true)) {
            $element->removeAttribute('href');
        }

        if (! in_array($element->getAttribute('target'), ['', '_blank', '_self'], true)) {
            $element->removeAttribute('target');
        }

        if ($element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private function isSafeUrl(string $url, bool $allowContactProtocols): bool
    {
        $decodedUrl = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($decodedUrl === '') {
            return false;
        }

        if (str_starts_with($decodedUrl, '/') || str_starts_with($decodedUrl, '#')) {
            return true;
        }

        $allowedProtocols = $allowContactProtocols ? ['http', 'https', 'mailto', 'tel'] : ['http', 'https'];
        $scheme = strtolower((string) parse_url($decodedUrl, PHP_URL_SCHEME));

        return in_array($scheme, $allowedProtocols, true);
    }

    private function sanitizeStyle(string $style): ?string
    {
        $allowedProperties = [
            'background-color', 'color', 'font-family', 'font-size', 'line-height', 'text-align', 'text-decoration',
        ];
        $safeDeclarations = collect(explode(';', $style))
            ->map(fn (string $declaration): array => array_pad(explode(':', $declaration, 2), 2, ''))
            ->filter(function (array $parts) use ($allowedProperties): bool {
                $property = strtolower(trim($parts[0]));
                $value = trim($parts[1]);

                return in_array($property, $allowedProperties, true)
                    && preg_match('/(?:url|expression|javascript|@import)/i', $value) !== 1
                    && mb_strlen($value) <= 100
                    && preg_match('/^[#(),.%\-\w\s\'\"]+$/u', $value) === 1;
            })
            ->map(fn (array $parts): string => strtolower(trim($parts[0])).':'.trim($parts[1]))
            ->implode(';');

        return $safeDeclarations !== '' ? $safeDeclarations : null;
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
