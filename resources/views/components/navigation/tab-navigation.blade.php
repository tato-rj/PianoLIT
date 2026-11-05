@php
    $syncHash = $syncHash ?? true;
    $active = $active ?? $items[0]['target'];
    $variantClass = ['segmented' => 'tab-navigation--segmented', 'segmented-bar' => 'tab-navigation--segmented-bar'][$variant ?? 'tabs'] ?? 'nav-tabs';
@endphp
@if($syncHash)
@once
<script>{!! file_get_contents(resource_path('js/components/tab-navigation.js')) !!}</script>
@endonce
<div data-tab-navigation-frame>
    <script>window.PianoLITTabNavigation.prepare(document.currentScript.parentElement, window);</script>
@endif
<ul class="nav {{ $variantClass }} {{ ($tone ?? '') === 'neutral' ? 'tab-navigation--neutral' : '' }} {{ ($variant ?? 'tabs') === 'segmented-bar' ? 'border' : '' }} {{ $class ?? '' }}" id="{{ $id }}" role="tablist" aria-label="{{ $label }}" @if($syncHash) data-tab-navigation data-tab-default="{{ $active }}" @endif @if(($variant ?? 'tabs') === 'segmented-bar') data-sliding-tabs @endif @if($preview ?? false) data-ds-preview @endif>
    @foreach($items as $item)
    @php
        $tabSwatch = $item['swatch'] ?? null;
        $tabSwatch = is_string($tabSwatch) && preg_match('/^#[a-f0-9]{6}$/iD', $tabSwatch) ? $tabSwatch : null;
        $tabSwatchSecondary = $item['swatchSecondary'] ?? null;
        $tabSwatchSecondary = is_string($tabSwatchSecondary) && preg_match('/^#[a-f0-9]{6}$/iD', $tabSwatchSecondary) ? $tabSwatchSecondary : $tabSwatch;
    @endphp
    <li class="nav-item" role="presentation">
        <a class="nav-link {{ $active === $item['target'] ? 'active' : '' }}" id="{{ $item['id'] ?? $id.'-'.$item['target'] }}" data-bs-toggle="tab" href="#{{ $item['target'] }}" role="tab" aria-controls="{{ $item['target'] }}" aria-selected="{{ $active === $item['target'] ? 'true' : 'false' }}" @foreach($item['data'] ?? [] as $attribute => $value) data-{{ $attribute }}="{{ $value }}" @endforeach @if($active !== $item['target']) tabindex="-1" @endif>@if($tabSwatch)<span class="tab-navigation__swatch" style="--swatch-color: {{ $tabSwatch }}; --swatch-color-secondary: {{ $tabSwatchSecondary }}" aria-hidden="true"></span>@endif{{ $item['label'] }}</a>
    </li>
    @endforeach
    @if(($variant ?? 'tabs') === 'segmented-bar')
    <li class="tab-navigation__selection border" role="presentation" aria-hidden="true"></li>
    @endif
</ul>
    {{ $slot }}
@if($syncHash)
    <script>window.PianoLITTabNavigation.initial(document.currentScript.parentElement, window);</script>
</div>
@endif
