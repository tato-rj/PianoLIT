<div class="list-group text-center">
	@foreach($options as $option => $tag)
	<button type="button" class="list-group-item list-group-item-action rounded-0 py-4" data-tag="{{$tag}}">{{$option}}</button>
	@endforeach
</div>