@include('webapp.piece.components.saveto.header')
@include('webapp.piece.components.saveto.new')
<div class="px-3 pt-1 mt-2 custom-scroll dragscroll dragscroll-horizontal" style="max-height: 280px">
	@foreach($folders as $folder)
		@include('webapp.piece.components.saveto.folder')
	@endforeach
</div>