@props(['items' => []])

<nav aria-label="Breadcrumb" class="text-xs text-slate-500">
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                @if (! $loop->last && ! empty($item['url']))
                    <a href="{{ $item['url'] }}" class="app-focus rounded hover:text-sky-700">{{ $item['label'] }}</a>
                    <span aria-hidden="true">/</span>
                @else
                    <span @if($loop->last) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
