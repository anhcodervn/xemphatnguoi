<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CustomMetaTags
{
    /**
     * @return array<int, array{attribute: 'name'|'property', key: string, content: string}>
     */
    public function parse(?string $html): array
    {
        $html = trim((string) $html);

        if ($html === '') {
            return [];
        }

        if (Str::length($html) > 10000) {
            throw new InvalidArgumentException('Custom header không được vượt quá 10.000 ký tự.');
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousUseInternalErrors = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadHTML(
                '<?xml encoding="UTF-8"><!DOCTYPE html><html><head>'.$html.'</head><body></body></html>',
                LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET,
            );
            $parseErrors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousUseInternalErrors);
        }

        if ($loaded !== true || $parseErrors !== []) {
            throw new InvalidArgumentException('Custom header chứa HTML không hợp lệ.');
        }

        $head = $document->getElementsByTagName('head')->item(0);
        $body = $document->getElementsByTagName('body')->item(0);

        if (! $head instanceof DOMElement || ! $body instanceof DOMElement || $this->containsContent($body)) {
            throw new InvalidArgumentException('Custom header chỉ được chứa các thẻ meta.');
        }

        $tags = [];

        foreach ($head->childNodes as $node) {
            if ($this->isIgnorableWhitespace($node)) {
                continue;
            }

            if (! $node instanceof DOMElement || strtolower($node->tagName) !== 'meta') {
                throw new InvalidArgumentException('Custom header chỉ được chứa các thẻ meta.');
            }

            $tags[] = $this->parseMetaElement($node);

            if (count($tags) > 20) {
                throw new InvalidArgumentException('Custom header chỉ được chứa tối đa 20 thẻ meta.');
            }
        }

        return $tags;
    }

    /**
     * @return array<int, array{attribute: 'name'|'property', key: string, content: string}>
     */
    public function parseForRendering(?string $html): array
    {
        try {
            return $this->parse($html);
        } catch (InvalidArgumentException) {
            return [];
        }
    }

    /**
     * @return array{attribute: 'name'|'property', key: string, content: string}
     */
    private function parseMetaElement(DOMElement $element): array
    {
        $allowedAttributes = ['name', 'property', 'content'];

        foreach ($element->attributes as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowedAttributes, true)) {
                throw new InvalidArgumentException('Thẻ meta chỉ được dùng thuộc tính name hoặc property và content.');
            }
        }

        $hasName = $element->hasAttribute('name');
        $hasProperty = $element->hasAttribute('property');

        if ($hasName === $hasProperty || ! $element->hasAttribute('content')) {
            throw new InvalidArgumentException('Mỗi thẻ meta cần dùng name hoặc property và bắt buộc có content.');
        }

        $attribute = $hasName ? 'name' : 'property';
        $key = trim($element->getAttribute($attribute));
        $content = trim($element->getAttribute('content'));

        if ($key === '' || $content === '') {
            throw new InvalidArgumentException('Thuộc tính và nội dung của thẻ meta không được để trống.');
        }

        if (Str::length($key) > 190 || Str::length($content) > 2048) {
            throw new InvalidArgumentException('Thuộc tính hoặc nội dung của thẻ meta quá dài.');
        }

        if ($this->isApplicationOwnedMeta($attribute, $key)) {
            throw new InvalidArgumentException('Thẻ meta này đã được hệ thống quản lý và không thể ghi đè.');
        }

        return [
            'attribute' => $attribute,
            'key' => $key,
            'content' => $content,
        ];
    }

    private function containsContent(DOMElement $element): bool
    {
        foreach ($element->childNodes as $node) {
            if (! $this->isIgnorableWhitespace($node)) {
                return true;
            }
        }

        return false;
    }

    private function isIgnorableWhitespace(DOMNode $node): bool
    {
        return $node->nodeType === XML_TEXT_NODE && trim((string) $node->nodeValue) === '';
    }

    private function isApplicationOwnedMeta(string $attribute, string $key): bool
    {
        $normalizedKey = Str::lower($key);

        if ($attribute === 'name') {
            return in_array($normalizedKey, ['viewport', 'csrf-token', 'description', 'robots', 'referrer'], true)
                || Str::startsWith($normalizedKey, 'twitter:');
        }

        return Str::startsWith($normalizedKey, ['og:', 'twitter:']);
    }
}
