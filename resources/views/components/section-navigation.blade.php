<nav id="{{ $id }}" class="section-navigation" aria-label="{{ $label }}" @if($preview ?? false) data-ds-preview @else data-section-navigation @endif>
    @if(! empty($heading))<p class="small text-muted mb-3">{{ $heading }}</p>@endif
    <ul class="section-navigation__links list-unstyled mb-0">
        @foreach($items as $item)
            <li>
                <a href="#{{ $item['target'] }}" @if(($preview ?? false) && $loop->first) aria-current="location" @endif>
                    @if(! empty($item['icon']))@icon($item['icon'])@endif{{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
@unless($preview ?? false)
    @once
        @push('scripts')
            <script src="{{ mix('js/components/section-navigation.js') }}" defer></script>
        @endpush
    @endonce
@endunless
