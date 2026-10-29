<nav class="mb-3" aria-label="Breadcrumb" @if($preview ?? false) data-ds-preview @endif>
    <ol class="breadcrumb public-breadcrumb bg-transparent p-0 mb-0">
        @foreach($items as $item)
            <li class="breadcrumb-item text-break{{ $loop->last ? ' active' : '' }}" @if($loop->last) aria-current="page" @endif>
                @if(! $loop->last && ! empty($item['url']))
                    @if($loop->first)
                    @icon('home', ['mr' => 1])
                    @endif
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    {{ $item['label'] }}
                @endif
            </li>
        @endforeach
    </ol>
</nav>
