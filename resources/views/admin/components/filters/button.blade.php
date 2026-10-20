<button type="{{ !empty($preview) ? 'button' : ($button['type'] ?? 'submit') }}" class="btn btn-sm {{ ($button['secondary'] ?? false) ? 'btn-secondary admin-filter-button--secondary' : 'btn-dark' }} admin-filter-button {{ $button['class'] ?? '' }}"
    @isset($button['name']) name="{{ $button['name'] }}" value="{{ $button['value'] ?? '' }}" @endisset
    @isset($button['title']) title="{{ $button['title'] }}" @endisset
    @isset($button['ariaLabel']) aria-label="{{ $button['ariaLabel'] }}" @endisset
    @foreach($button['data'] ?? [] as $attribute => $attributeValue) data-{{ $attribute }}="{{ $attributeValue }}" @endforeach
    @if($button['disabled'] ?? false) disabled @endif
>@if(!empty($button['icon']))@icon($button['icon'], ['mr' => 0])@endif<span>{{ $button['label'] ?? 'Apply filters' }}</span></button>
