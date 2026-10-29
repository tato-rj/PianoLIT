<nav id="{{ $id }}" class="section-navigation" aria-label="{{ $label }}" @if($preview ?? false) data-ds-preview @elseif($trackSections ?? true) data-section-navigation @endif>
    <ul class="section-navigation__links list-unstyled mb-0">
        @foreach($items as $item)
            <li>
                <a href="{{ $item['url'] ?? '#'.$item['target'] }}" @if($item['current'] ?? false) aria-current="page" @elseif(($preview ?? false) && $loop->first) aria-current="location" @endif>
                    @if(! empty($item['icon']))@icon($item['icon'])@endif{{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
@if(! ($preview ?? false) && ($trackSections ?? true))
    @once
        @push('scripts')
            <script src="{{ mix('js/components/section-navigation.js') }}" defer></script>
        @endpush
    @endonce
@endif
