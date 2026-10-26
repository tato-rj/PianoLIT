@once
<script>{!! file_get_contents(resource_path('js/components/tab-navigation.js')) !!}</script>
@endonce
<div data-tab-navigation-frame>
    <script>window.PianoLITTabNavigation.prepare(document.currentScript.parentElement, window);</script>
@php($active = $active ?? $items[0]['target'])
<ul class="nav {{ ($variant ?? 'tabs') === 'segmented' ? 'tab-navigation--segmented' : 'nav-tabs' }} {{ $class ?? '' }}" id="{{ $id }}" role="tablist" aria-label="{{ $label }}" data-tab-navigation data-tab-default="{{ $active }}" @if($preview ?? false) data-ds-preview @endif>
    @foreach($items as $item)
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === $item['target'] ? 'active' : '' }}" id="{{ $item['id'] ?? $id.'-'.$item['target'] }}" data-bs-toggle="tab" href="#{{ $item['target'] }}" role="tab" aria-controls="{{ $item['target'] }}" aria-selected="{{ $active === $item['target'] ? 'true' : 'false' }}" @foreach($item['data'] ?? [] as $attribute => $value) data-{{ $attribute }}="{{ $value }}" @endforeach @if($active !== $item['target']) tabindex="-1" @endif>{{ $item['label'] }}</a>
    </li>
    @endforeach
</ul>
    {{ $slot }}
    <script>window.PianoLITTabNavigation.initial(document.currentScript.parentElement, window);</script>
</div>
