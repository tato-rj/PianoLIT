<div class="justify-content-end d-flex">
	{{$slot ?? null}}
	@foreach($actions as $folder => $action)
	@include('components.datatable.actions.' . $folder)
	@endforeach
</div>