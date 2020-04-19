@component('components.draggable.cards.small', ['model' => $piece])

{{$piece->short_name}} by {{$piece->composer->short_name}}

@slot('actions')
  @include('admin.components.play', ['audio' => storage($piece->audio_path)])
  <div class="mx-2">
    @if($piece->is_public_domain)        
    <a href="{{storage($piece->score_path)}}" target="_blank" class="{{$piece->lookup('score_path')}}"><i class="fas fa-file-alt"></i></a>
    @else
    <a href="{{$piece->score_url}}" target="_blank" class="test-success"><i class="fas fa-globe"></i></a>
    @endif
  </div>
@endslot
@endcomponent