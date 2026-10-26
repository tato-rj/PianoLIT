<div class="edit-control d-flex align-items-center selected-piece ordered t-2 roundedX bg-whiteX hover-shadow-lightX mb-2" data-id="{{$model->id}}" style="padding: .375rem 0; user-select: none;">
  <div class="ms-2 text-muted opacity-4">{{! empty($loop) ? $loop->iteration : null}}</div>
  <div class="flex-grow d-flex text-truncate">
    {{-- SORT HANDLE --}}
    <div class="px-2 me-1 sort-handle">
      @icon('arrow-down-up', ['mr' => 0])
    </div>
    <div class="d-flex align-items-center text-truncate">

      {{ $actions ?? null }}

      <input type="hidden" name="{{$model->getTable()}}[]" value="{{$model->id}}">

      <p class="m-0 text-truncate">{{ $slot }}</p>
    </div>
  </div>
  @empty($controls)
  <div class="text-end px-1 remove">
    @icon('circle-x', ['mr' => 0, 'classes' => 'text-danger mx-2 cursor-pointer'])
  </div>
  @else
  <div class="text-end px-1">
  {{ $controls }}
  </div>
  @endempty
</div>
