@if(url()->previous() != url()->current())
<a class="btn-raw position-absolute link-none" href="{{url()->previous()}}" style="left: 0; bottom: 50%; transform: translateY(50%); font-size: 1.44em">
	@fa(['icon' => 'chevron-left'])</a>
@endif