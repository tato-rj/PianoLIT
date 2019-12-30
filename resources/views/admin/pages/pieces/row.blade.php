<tr>
  <td class="d-flex">
    <div class="dropdown d-inline-block align-text-bottom cursor-pointer mr-1">
      <i class="fas fa-ellipsis-v dropdown-toggle" data-toggle="dropdown"></i>
      <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        <a href="{{$item->timeline_url}}" target="_blank" class="dropdown-item">Timeline</a>
        <a href="{{route('api.pieces.collection', $item->id)}}" target="_blank" class="dropdown-item">Collection</a>
        <a href="{{route('api.pieces.similar', $item->id)}}" target="_blank" class="dropdown-item">More like this</a>
        <a href="{{route('pieces.show', $item->id)}}" target="_blank" class="dropdown-item">Score</a>
      </div>
    </div>
    <div class="d-flex hide-on-sm">
      <span class="{{$item->curiosity ? 'text-primary' : 'text-muted'}}" title="{{$item->curiosity}}"><i class="fas fa-info-circle mr-1"></i></span>
      @include('admin.components.play', ['audio' => storage($item->audio_path)])
      <span class="mx-1 {{$item->videos_count > 0 ? 'text-primary' : 'text-muted'}}"><i class="fab fa-youtube mr-1"></i>{{$item->videos_count}}</span>
      <span class="{{$item->itunes_count > 0 ? 'text-primary' : 'text-muted'}}"><i class="fab fa-itunes mr-1"></i>{{$item->itunes_count}}</span>
    </div>
  </td>

  <td class="dataTables_main_column">{{$item->long_name}}
    @if(! $item->hasAudio())
    <a href="{{youtube($item->long_name . ' by ' . $item->composer->name)}}" target="_blank" class="link-blue"><i class="fas fa-external-link-alt ml-1 fa-xs"></i></a>
    @endif
  </td>

  <td class="text-nowrap">{{$item->composer->short_name}}</td>

  <td class="position-relative">
    <span class="badge badge-light badge-popup cursor-pointer" id="badge-tag-{{$item->id}}">{{$item->tags_count}}</span>
    <div class="position-absolute bg-white shadow-sm border p-2 rounded popup mb-3 tags-quick-edit" 
      data-url="{{route('admin.pieces.load-tags', $item->id)}}" 
      style="top: 10px; display: none; z-index: 2; right: 0; width: 720px">
      @include('admin.pages.pieces.popups.content')
    </div>
  </td>

  <td class="position-relative">
    <div class="badge-popup cursor-pointer badge badge-pill bg-{{strtolower($item->level->name)}}" 
        data-original-class="bg-{{strtolower($item->level->name)}}" 
        data-original-id="{{$item->level->id}}" 
        id="badge-level-{{$item->id}}">{{ucfirst($item->level->name)}}</div>
    <div class="position-absolute bg-white shadow-sm border px-2 pt-2 pb-1 rounded popup mb-3"  data-url="{{route('admin.pieces.load-levels', $item->id)}}" style="top: 10px; display: none; z-index: 1; left: 0">
    @include('admin.pages.pieces.popups.content')
    </div>
  </td>

  <td class="text-norwap">
    @if($rcm = $item->getRanking('rcm'))
    <div class="badge badge-pill alert-blue"><strong>RCM {{$rcm}}</strong></div>
    @endif
    @if($abrsm = $item->getRanking('abrsm'))
    <div class="badge badge-pill alert-blue"><strong>ABRSM {{$abrsm}}</strong></div>
    @endif
  </td>

  @component('components.datatable.actions', ['actions' => [
      'edit' => route('admin.pieces.edit', $item->id),
      'delete' => route('admin.pieces.destroy', $item->id)
  ]])
  @if($item->is_free)
  <div>
    <button class="border-0 p-0 bg-transparent text-success mr-2 align-middle" disabled><i class="fas fa-award"></i></button>
  </div>
  @else
  <form method="POST" action="{{route('admin.pieces.highlight', $item->id)}}">
    @csrf
    @method('PATCH')
    <button type="submit" class="border-0 p-0 bg-transparent text-grey mr-2 align-middle" id="{{$item->hasImage() ? null : 'missing-image'}}" title="Highlight this piece"><i class="fas fa-award"></i></button>
  </form>
  @endif
  @endcomponent

</tr>
