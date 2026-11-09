@php($menuItemActive = ($navigationPreview ?? false) ? $label === 'Discover' : (url()->current() == $url || ($label === 'Explore' && request()->routeIs('webapp.composers.*'))))
<div class="text-center position-relative" @unless($navigationPreview ?? false) id="menu-{{str_slug($label)}}" @endunless>
	<a class="menu-link {{ $menuItemActive ? 'active' : '' }}" href="{{ ($navigationPreview ?? false) ? '#' : $url }}" aria-label="{{ $label }}" @if($menuItemActive) aria-current="page" @endif>
		<div class="webapp-menu-icon-row">@icon($icon, ['classes' => 'menu-icon', 'mr' => 0])</div>
		<div><small class="webapp-menu-label">{{$label}}</small></div>
		<small>@icon('circle', ['color' => 'orange', 'styles' => 'position: absolute;
																		    top: -6px;
																		    right: 0px;
																		    font-size: 60%;', 'if' => $new ?? false, 'filled' => true])</small>
	</a>
</div>
