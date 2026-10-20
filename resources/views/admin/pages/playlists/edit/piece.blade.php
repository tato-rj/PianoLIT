@component('components.draggable.cards.small', ['model' => $piece])

{{$piece->short_name}} by {{$piece->composer->short_name}}

@slot('actions')
  @include('admin.components.play', ['audio' => storage($piece->audio_path)])
  @include('admin.components.table-action', ['tableAction' => ['url' => $piece->is_public_domain ? (string) storage($piece->score_path) : (string) $piece->score_url, 'label' => 'View score', 'icon' => $piece->is_public_domain ? 'file-text' : 'globe', 'iconOnly' => true, 'class' => 'mx-2', 'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]])
@endslot
@endcomponent