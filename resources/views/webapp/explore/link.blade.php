<a href="{{ $href }}" class="explore-link link-none {{ $classes ?? '' }}" @if(!empty($current)) aria-current="page" @endif>
    @if(!empty($image))
        <img src="{{ $image }}" alt="" class="explore-artwork" width="56" height="56" loading="lazy">
    @else
        @isset($icon)<span class="explore-icon {{ $iconClass ?? '' }}">@icon($icon, ['mr' => 0, 'size' => 'lg'])</span>@endisset
    @endif
    <span class="explore-copy"><span class="{{ !empty($description) ? 'fw-bold' : '' }}">{{ $label }}</span>@if(!empty($description))<small class="d-block text-muted">{{ $description }}</small>@endif</span>
    @icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])
</a>
