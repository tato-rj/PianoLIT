<div class="text-center">
	<a class="menu-link {{url()->current() == $url ? 'active' : null}}" href="{{$url}}">
		<div>@fa(['icon' => $icon, 'classes' => 'menu-icon', 'mr' => 0])</div>
		<div><small>{{$label}}</small></div>
	</a>
</div>