<a href="{{ $href }}" class="explore-link link-none {{ $classes ?? '' }}" @if(!empty($current)) aria-current="page" @endif>
    @isset($icon)<span class="explore-icon {{ $iconClass ?? '' }}">@icon($icon, ['mr' => 0, 'size' => 'lg'])</span>@endisset
    <span class="explore-copy"><span class="{{ !empty($description) ? 'fw-bold' : '' }}">{{ $label }}</span>@if(!empty($description))<small class="d-block text-muted">{{ $description }}</small>@endif</span>
    @icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])
</a>
