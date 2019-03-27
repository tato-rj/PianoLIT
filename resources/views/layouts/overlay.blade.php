<div id="{{$name}}-overlay" class="position-fixed w-100 h-100vh z-40" style="top: 0; left: 0; display: none;">
	<button data-target="#{{$name}}-overlay" class="close-overlay absolute-top-right bg-transparent border-0"><img src="{{asset('images/icons/close-dark.svg')}}" width="20"></button>
	<div class="w-100 h-100" style="background: rgba(255,255,255,{{$opacity ?? 0.94}});">

			{{$slot}}

	</div>
</div>