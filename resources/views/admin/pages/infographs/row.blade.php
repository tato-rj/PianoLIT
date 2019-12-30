<tr>
	@include('components.datatable.date', ['date' => $item->created_at])

	<td class="dataTables_main_column">{{$item->name}}</td>

	<td>{{$item->downloads}} {{str_plural('time', $item->downloads)}}</td>

	<td>{{$item->score}} {{str_plural('point', $item->score)}}</td>
	
	<td>
		@toggle(['toggle' => $item->published_at, 'route' => route('admin.infographs.update-status', ['infograph' => $item->slug, 'attribute' => 'published_at'])])
	</td>

	<td>
		@toggle(['toggle' => $item->giftable_at, 'route' => route('admin.infographs.update-status', ['infograph' => $item->slug, 'attribute' => 'giftable_at'])])
	</td>

  @component('components.datatable.actions', ['actions' => [
      'edit' => route('admin.infographs.edit', $item->slug),
      'delete' => route('admin.infographs.destroy', $item->slug)
  ]])
	<a href="#" data-toggle="modal" title="Preview this infograph" data-thumbnail="{{storage($item->thumbnail_path)}}" data-image="{{storage($item->cover_path)}}" data-target="#item-preview" class="text-muted mr-2">
		<i class="fas fa-eye align-middle"></i>
	</a>
  @endcomponent
</tr>