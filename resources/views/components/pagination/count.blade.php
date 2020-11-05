@if($collection)
<div class="text-{{$align ?? 'center'}} text-muted mb-2">
Showing {{$collection->firstItem()}}-{{$collection->lastItem()}} of {{$collection->total()}} {{str_plural($item ?? 'item', $collection->total())}}
</div>
@endif