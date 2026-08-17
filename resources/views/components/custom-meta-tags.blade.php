@foreach ($tags as $tag)
    <meta {{ $tag['attribute'] }}="{{ $tag['key'] }}" content="{{ $tag['content'] }}">
@endforeach
