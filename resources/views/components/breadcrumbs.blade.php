@props(['items' => []])

<nav class="mb-5 text-sm text-stone-500" aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-2">
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                @if (! $loop->first)
                    <span>/</span>
                @endif

                @if (isset($item['url']))
                    <a href="{{ $item['url'] }}" class="transition hover:text-stone-800">{{ $item['label'] }}</a>
                @else
                    <span class="font-medium text-stone-800">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
