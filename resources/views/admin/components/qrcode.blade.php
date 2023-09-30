@if(auth()->check() && auth()->user()->isAdmin())
<div class="position-fixed" 
	style="top: 0;
	    left: 50%;
	    transform: translateX(-50%);
	    z-index: 10000000;">
	<a href="{{route('qrcode.download')}}" target="_blank" class="btn btn-sm btn-warning" 
	style="border-top-left-radius: 0!important;
    	border-top-right-radius: 0!important;">@fa(['icon' => 'qrcode', 'mr' => 0])</a>
</div>
@endif