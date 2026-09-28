@component('components.draggable.cards.small', ['model' => $piece])

{{$piece->short_name}} by {{$piece->composer->short_name}}

@slot('actions')
  @include('admin.components.play', ['audio' => storage($piece->audio_path)])
  <div class="mx-2">
    @if($piece->is_public_domain)        
    <a href="{{storage($piece->score_path)}}" target="_blank" class="{{$piece->lookup('score_path')}}">@icon('file-text', ['mr' => 0])</a>
    @else
    <a href="{{$piece->score_url}}" target="_blank" class="test-success">@icon('globe', ['mr' => 0])</a>
    @endif
  </div>
@endslot
@endcomponent