<div class="custom-scroll dragscroll dragscroll-horizontal card-scroll-row" @if(!empty($id)) id="{{ $id }}" @endif>
	<div class="d-flex pb-2">
		{{$slot}}
	</div>
</div>
