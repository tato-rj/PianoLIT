<nav class="mb-4" aria-label="Breadcrumb" @if($preview ?? false) data-ds-preview @endif>
    <ol class="breadcrumb bg-transparent p-0 mb-0 small">
        @foreach($items as $item)
            <li class="breadcrumb-item text-break{{ $loop->last ? ' active' : '' }}" @if($loop->last) aria-current="page" @endif>
                @if(! $loop->last && ! empty($item['url']))
                    <a class="link-blue" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    {{ $item['label'] }}
                @endif
            </li>
        @endforeach
    </ol>
</nav>
