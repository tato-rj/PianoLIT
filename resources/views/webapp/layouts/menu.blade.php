<div id="menu" class="border-top border-grey-light container-fluid bg-white z-10" style="position: fixed; bottom: 0; left: 0;">
	<div class="row">
		<div class="col-lg-6 col-md-8 col-12 mx-auto py-3 d-flex justify-content-around">
			@include('webapp.layouts.menu-item', ['url' => route('webapp.discover'), 'icon' => 'music', 'label' => 'Discover'])
			@include('webapp.layouts.menu-item', ['url' => route('webapp.explore'), 'icon' => 'search', 'label' => 'Explore'])
			@include('webapp.layouts.menu-item', ['url' => route('webapp.playlists'), 'icon' => 'layer-group', 'label' => 'Playlists'])
			@include('webapp.layouts.menu-item', ['url' => route('webapp.my-pieces'), 'icon' => 'heart', 'label' => 'My pieces'])
			@include('webapp.layouts.menu-item', ['url' => route('webapp.settings'), 'icon' => 'cog', 'label' => 'Settings'])
		</div>
	</div>
</div>