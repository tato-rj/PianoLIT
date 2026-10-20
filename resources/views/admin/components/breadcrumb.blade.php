<nav class="admin-breadcrumbs" aria-label="Breadcrumb" @if($designSystemPreview ?? false) data-ds-preview @endif>
    <ol class="admin-breadcrumbs__list">
        @foreach($items as $item)
        <li>
            @unless($loop->first)<span class="admin-breadcrumbs__separator" aria-hidden="true">@icon('chevron-right', ['mr' => 0])</span>@endunless
            @if($loop->last)
                <span aria-current="page">{{ $item['label'] }}</span>
            @elseif(!empty($item['url']))
                <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
            @else
                <span>{{ $item['label'] }}</span>
            @endif
        </li>
        @endforeach
    </ol>
</nav>
