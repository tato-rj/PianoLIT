<div class="text-center position-relative">
	<a class="menu-link {{url()->current() == $url ? 'active' : null}}" href="{{$url}}">
		<div>@fa(['icon' => $icon, 'classes' => 'menu-icon', 'mr' => 0])</div>
		<div><small>{{$label}}</small></div>
		<small>@pill(['theme' => 'green', 'label' => 'NEW!', 'styles' => 'position: absolute; top: 0;', 'if' => $new ?? false])</small>
	</a>
</div>