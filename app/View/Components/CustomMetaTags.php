<?php

namespace App\View\Components;

use App\Support\CustomMetaTags as CustomMetaTagsParser;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CustomMetaTags extends Component
{
    /**
     * @var array<int, array{attribute: 'name'|'property', key: string, content: string}>
     */
    public readonly array $tags;

    public function __construct(CustomMetaTagsParser $customMetaTags, string $html = '')
    {
        $this->tags = $customMetaTags->parseForRendering($html);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.custom-meta-tags');
    }
}
