<div class="edit-control border d-flex align-items-center selected-piece ordered t-2 rounded bg-white hover-shadow-light mb-2" data-id="{{$model->id}}" style="padding: .375rem 0; user-select: none;">
  <div class="ml-2 text-muted opacity-4">{{! empty($loop) ? $loop->iteration : null}}</div>
  <div class="sort-handle flex-grow d-flex text-truncate">
    {{-- SORT HANDLE --}}
    <div class="px-2 mr-1">
      <i class="fas fa-sort"></i>
    </div>
    <div class="d-flex align-items-center text-truncate">

      {{ $actions ?? null }}

      <input type="hidden" name="{{$model->getTable()}}[]" value="{{$model->id}}">

      <p class="m-0 text-truncate" style="user-select: none;">{{ $slot }}</p>
    </div>
  </div>
  @empty($controls)
  <div class="text-right px-1 remove">
    <i class="fas text-danger fa-times-circle mx-2 cursor-pointer"></i>
  </div>
  @else
  <div class="text-right px-1">
  {{ $controls }}
  </div>
  @endempty
</div>
