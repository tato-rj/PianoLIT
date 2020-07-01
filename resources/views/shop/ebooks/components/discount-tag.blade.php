@if($ebook->discount > 0 && $ebook->discount < 100)
<div class="absolute-top-{{$position}} bg-red text-white px-2 py-1">
	<span><strong>{{$ebook->discount}}%</strong> OFF!</span>
</div>
@endif