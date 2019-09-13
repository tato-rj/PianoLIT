<div id="{{$name}}-overlay" class="position-fixed w-100 h-100vh z-40 bg-{{$window_bg ?? null}}" style="top: 0; left: 0; display: none; overflow-y: auto">
	<button class="absolute-top-right bg-transparent border-0">
		<img data-target="#{{$name}}-overlay" class="close-overlay mt-2" src="{{$light ? asset('images/icons/close-dark.svg') : asset('images/icons/close.svg')}}" width="30">
	</button>
	<div data-target="#{{$name}}-overlay" class="close-overlay d-flex justify-content-center align-items-{{$position}} w-100 h-100" style="background: rgba({{$background ?? '255,255,255,0.94'}});">

			{{$slot}}

	</div>
</div>