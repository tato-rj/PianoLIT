<div class="edit-control border d-flex align-items-center selected-piece ordered t-2 rounded bg-white hover-shadow-light mb-2" style="padding: .375rem 0;">
  <div class="sort-handle cursor-pointer flex-grow d-flex">
    {{-- SORT HANDLE --}}
    <div class="px-3">
      <i class="fas fa-sort"></i>
    </div>
    {{-- INPUTS --}}
    <div class="d-flex align-items-center">
      @include('admin.components.play', ['audio' => storage($piece->audio_path)])
      <div class="ml-2 {{$piece->lookup('score_path')}}">
        <a href="{{storage($piece->score_path)}}" target="_blank" class="text-muted"><i class="fas fa-file-alt"></i></a>
      </div>
      <input type="hidden" name="pieces[]" value="{{$piece->id}}">
      <p class="m-0 ml-2 piece-name">{{$piece->short_name}} by {{$piece->composer->short_name}}</p>
    </div>
  </div>
  {{-- ACTION BUTTONS --}}
  <div class="text-right px-1 remove">
    <i class="fas text-danger fa-times-circle mx-2 cursor-pointer"></i>
  </div>
</div>
