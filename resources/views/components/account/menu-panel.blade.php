@php($panelPreview = $designSystemPreview ?? false)
<div class="dropdown-menu dropdown-menu-end account-menu {{ $menuClasses ?? '' }}{{ $panelPreview ? ' d-block position-static' : '' }}" aria-labelledby="{{ $menuId ?? 'navbarDropdown' }}" @if($panelPreview) data-ds-preview @endif>
    {{ $slot }}
</div>
@unless($panelPreview)
    @once
        @push('scripts')
            <script src="{{ mix('js/components/account-menu.js') }}"></script>
        @endpush
    @endonce
@endunless
