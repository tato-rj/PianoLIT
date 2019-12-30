<tr class="{{($item->isPublished()) ? 'opacity-4' : null}}">
  @include('components.datatable.date', ['date' => $item->created_at])

  <td class="align-middle text-nowrap">{{$item->isPublished() ? $item->created_at->toFormattedDateString() : null}}</td>

  <td class="align-middle">{{$item->piece->medium_name_with_composer}}</td>

  <td class="align-middle">{{$item->user->full_name}}</td>
  
  <td class="text-right text-nowrap">
    @if($item->isPublished())
    <div class="text-success"><i class="fas fa-check-circle mr-1"></i>Published</div>
    @else
    <a href="#" data-url="{{route('admin.tutorial-requests.publish', $item->id)}}" data-toggle="modal" data-target="#modal-publish-tutorial" class="btn btn-sm btn-warning"><i class="fas fa-hourglass-half mr-1"></i>Publish</a>
    @endif
  </td>
</tr>