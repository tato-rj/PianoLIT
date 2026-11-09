@php($navigationPreview = $navigationPreview ?? false)
<nav @unless($navigationPreview) id="menu" data-webapp-navigation @endunless aria-label="Web app" class="webapp-navigation {{ $navigationPreview ? 'webapp-navigation--preview' : '' }} {{ !empty($hideMobileMenu) ? 'd-none d-lg-block' : '' }}">
	<div class="row g-0">
		<div class="webapp-menu-items col-12 d-flex justify-content-around">
			@include('webapp.layouts.menu-item', ['url' => route('webapp.discover'), 'icon' => 'music', 'label' => 'Discover'])
			@include('webapp.layouts.menu-item', ['url' => route('webapp.explore'), 'icon' => 'search', 'label' => 'Explore'])
			@include('webapp.layouts.menu-item', ['url' => route('webapp.playlists'), 'icon' => 'layers', 'label' => 'Collections'])
			@include('webapp.layouts.menu-item', ['url' => route('webapp.my-pieces'), 'icon' => 'heart', 'label' => 'My pieces'])
			@if($navigationPreview)
				<div class="text-center position-relative">@include('components.account.account-circle', ['account' => null, 'variant' => 'webapp-navigation', 'designSystemPreview' => true])</div>
			@else
				@include('layouts.menu.user', ['webappNavigation' => true])
			@endif
		</div>
	</div>

	@unless($navigationPreview) @include('webapp.components.popup') @endunless
</nav>
