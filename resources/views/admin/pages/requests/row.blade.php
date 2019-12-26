<tr class="{{($request->isPublished()) ? 'opacity-4' : null}}">
  <td class="align-middle" style="white-space: nowrap;">{{$request->created_at->toFormattedDateString()}}</td>
  <td class="align-middle" style="white-space: nowrap;">{{$request->isPublished() ? $request->created_at->toFormattedDateString() : null}}</td>
  <td class="align-middle">{{$request->piece->medium_name_with_composer}}</td>
  <td class="align-middle">{{$request->user->full_name}}</td>
  <td class="text-right" style="white-space: nowrap;">
    @if($request->isPublished())
    <div class="text-success"><i class="fas fa-check-circle mr-1"></i>Published</div>
    @else
    <a href="#" data-url="{{route('admin.tutorial-requests.publish', $request->id)}}" data-toggle="modal" data-target="#modal-publish-tutorial" class="btn btn-sm btn-warning"><i class="fas fa-hourglass-half mr-1"></i>Publish</a>
    @endif
  </td>
</tr>