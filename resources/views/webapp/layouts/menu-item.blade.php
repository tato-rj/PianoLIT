<div class="text-center position-relative" id="menu-{{str_slug($label)}}">
	<a class="menu-link {{url()->current() == $url || ($label === 'Explore' && request()->routeIs('webapp.composers.*')) ? 'active' : null}}" href="{{$url}}">
		<div class="webapp-menu-icon-row">@icon($icon, ['classes' => 'menu-icon', 'mr' => 0])</div>
		<div><small class="webapp-menu-label">{{$label}}</small></div>
		<small>@icon('circle', ['color' => 'orange', 'styles' => 'position: absolute;
																		    top: -6px;
																		    right: 0px;
																		    font-size: 60%;', 'if' => $new ?? false, 'filled' => true])</small>
	</a>
</div>
