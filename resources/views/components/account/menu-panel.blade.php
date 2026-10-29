@php($panelPreview = $designSystemPreview ?? false)
<div class="dropdown-menu dropdown-menu-end account-menu {{ $menuClasses ?? '' }}{{ $panelPreview ? ' d-block position-static' : '' }}" aria-labelledby="{{ $menuId ?? 'navbarDropdown' }}" @if($panelPreview) data-ds-preview @endif>
    {{ $slot }}
</div>
@unless($panelPreview)
    @include('components.account.scripts')
@endunless
