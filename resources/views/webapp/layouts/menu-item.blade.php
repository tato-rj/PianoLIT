<div class="text-center position-relative" id="menu-{{str_slug($label)}}">
	<a class="menu-link {{url()->current() == $url ? 'active' : null}}" href="{{$url}}">
		<div>@fa(['icon' => $icon, 'classes' => 'menu-icon', 'mr' => 0])</div>
		<div><small>{{$label}}</small></div>
		<small>@fa(['icon' => 'circle', 'color' => 'orange', 'styles' => 'position: absolute;
																		    top: -6px;
																		    right: 0px;
																		    font-size: 60%;', 'if' => $new ?? false])</small>
	</a>
</div>