@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Pieces',
    'description' => 'Edit a piece'])
    
    <form id="edit-form" method="POST" action="{{route('admin.pieces.update', $piece->id)}}" enctype="multipart/form-data">
      @csrf
      @method('PATCH')
      <div class="row my-5 mx-2">
        <div class="col-lg-6 col-sm-10 col-12 mx-auto">
          <div class="px-3 py-2 rounded mb-4 bg-light">
            <i class="fas fa-eye text-brand mr-2"></i><small class="text-muted">{{$piece->views_count}} {{str_plural('view', $piece->views_count) }}</small>
          </div>

          {{-- Name --}}
          <div class="form-group form-row">
            <div class="col">
              <label class="text-brand"><small>Name</small></label>
              <input type="text" class="form-control" name="name" placeholder="Piece name" value="{{$piece->name}}" >
            </div>
            <div class="col">
              <label class="text-brand"><small>Nickname</small></label>
              <input type="text" class="form-control" name="nickname" placeholder="Nickname" value="{{$piece->nickname}}" >
            </div>
          </div>
          <div class="form-group form-row">
            <div class="col">
              <label class="text-brand"><small>Collection name</small></label>
              <input type="text" class="form-control" name="collection_name" placeholder="Collection name" value="{{$piece->collection_name}}">
            </div>
            <div class="col">
              <label class="text-brand"><small>Catalogue</small></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <select class="form-control rounded-left" style="border-radius: 0" name="catalogue_name" >
                    <option selected disabled>Catalogue</option>
                    @foreach(catalogues() as $catalogue)
                    <option value="{{$catalogue}}" {{($piece->catalogue_name == $catalogue) ? 'selected' : ''}}>{{$catalogue}}</option>
                    @endforeach
                  </select>
                </div>
                <input type="text" class="form-control" name="catalogue_number" placeholder="Catalogue number" value="{{$piece->catalogue_number}}">
              </div>
            </div>
          </div>
          {{-- Catalogue and number --}}
          <div class="form-row form-group">
            <div class="col">
              <label class="text-brand"><small>Collection number
                @include('admin.components.info', ['message' => 'This number will appear after the name of the piece. Ex: <i>Piece Name Op.1</i> <u>No.1</u>'])
              </small></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <div class="input-group-text">No.</div>
                </div>
                <input type="text" class="form-control" name="collection_number" placeholder="Number" value="{{$piece->collection_number}}">
              </div>
            </div>
            <div class="col">
              <label class="text-brand"><small>Movement number
                @include('admin.components.info', ['message' => 'This number will appear before the name of the piece: <u>1.</u> <i>Piece Name</i>'])
              </small></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <div class="input-group-text">Mov.</div>
                </div>
                <input type="text" class="form-control" name="movement_number" placeholder="Number" value="{{$piece->movement_number}}">
              </div>
            </div>
          </div>
          {{-- Key and Composer --}}
          <div class="form-row form-group">
            <div class="col">
              <label class="text-brand"><small>Composer</small></label>
              <select class="form-control" name="composer_id">
                <option selected disabled>Composer</option>
                @foreach($composers as $composer)
                <option value="{{$composer->id}}" {{($piece->composer_id == $composer->id) ? 'selected' : ''}}>{{$composer->short_name}}</option>
                @endforeach
              </select>
            </div>
            <div class="col">
              <label class="text-brand"><small>Composed in</small></label>
              <input type="number" min="1600" max="{{now()->year}}" class="form-control" name="composed_in" placeholder="Composed in" value="{{$piece->composed_in}}">
            </div>
            <div class="col">
              <label class="text-brand"><small>Key</small></label>
              <select class="form-control" name="key">
                <option selected disabled>Key</option>
                <optgroup label="Tonal">
                  @foreach(keys() as $key)
                  <option value="{{$key}}" {{($piece->key == $key) ? 'selected' : ''}}>{{$key}}</option>
                  @endforeach
                </optgroup>
                <optgroup label="Non-tonal">
                  <option value="Modal" {{($piece->key == 'Modal') ? 'selected' : ''}}>Modal</option>
                  <option value="Serial" {{($piece->key == 'Serial') ? 'selected' : ''}}>Serial</option>
                  <option value="Chromatic" {{($piece->key == 'Chromatic') ? 'selected' : ''}}>Chromatic</option>
                </optgroup>
              </select>
            </div>
          </div>
          {{-- Period, Length and Level --}}
          <div class="form-row form-group">
            <div class="col">
              <label class="text-brand"><small>Period</small></label>
              <select class="form-control" name="period[]" >
                <option selected disabled>Period</option>
                @foreach(\App\Tag::periods()->get() as $period)
                <option value="{{$period->id}}" {{($piece->period->name == $period->name) ? 'selected' : ''}}>{{ucfirst($period->name)}}</option>
                @endforeach
              </select>
            </div>
            <div class="col">
              <label class="text-brand"><small>Length</small></label>
              <select class="form-control" name="length[]" >
                <option selected disabled>Length</option>
                @foreach(\App\Tag::lengths()->get() as $length)
                <option value="{{$length->id}}" {{($piece->length->name == $length->name) ? 'selected' : ''}}>{{ucfirst($length->name)}}</option>
                @endforeach
              </select>
            </div>
            <div class="col">
              <label class="text-brand"><small>Level</small></label>
              <select class="form-control" name="level[]" >
                <option selected disabled>Level</option>
                @foreach(\App\Tag::levels()->get() as $level)
                <option value="{{$level->id}}" {{($piece->level->name == $level->name) ? 'selected' : ''}}>{{ucfirst($level->name)}}</option>
                @endforeach
              </select>
            </div>
          </div>
          {{-- Did you know? --}}
          <div class="form-group">
            <label class="text-brand"><small>Did you know?</small></label>
            <textarea class="form-control" rows="3" name="curiosity" placeholder="Enter a curiosity here">{{ $piece->curiosity }}</textarea>
          </div>
          @manager
          {{-- Score Info --}}
          <div class="form-group form-row">
            <div class="col">
              <label class="text-brand"><small>Score editor</small></label>
              <input type="text" class="form-control" name="score_editor" placeholder="Score editor" value="{{$piece->score_editor}}" >
            </div>
            <div class="col">
              <label class="text-brand"><small>Score copyright</small></label>
              <input type="text" class="form-control" name="score_copyright" placeholder="Score copyright" value="{{$piece->score_copyright}}">
            </div>
          </div>
          <div class="form-group">
              <label class="text-brand"><small>Score publisher</small></label>
              <input type="text" class="form-control" name="score_publisher" placeholder="Score publisher" value="{{$piece->score_publisher}}">
          </div>
          <div class="form-group">
              <label class="text-brand"><small>Score url (for non-public domain only)</small></label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <a class="input-group-text no-underline {{$piece->lookup('score_url')}}" href="{{$piece->score_url}}" target="_blank"><i class="fas fa-globe"></i></a>
                </div>
                <input type="text" class="form-control" name="score_url" placeholder="Score url" value="{{$piece->score_url}}">
              </div>
          </div>
          {{-- Files --}}
          <label class="text-brand"><small>Uploads</small></label>
          <div class="form-row form-group">
            <div class="col input-group">
              <div class="input-group-prepend">
                <a class="input-group-text no-underline {{$piece->lookup('audio_path')}}" href="{{storage($piece->audio_path)}}" target="_blank"><i class="fas fa-microphone"></i></a>
              </div>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="audio" id="customFile">
                <label class="custom-file-label truncate" for="customFile">Both hands</label>
              </div>
            </div>
            <div class="col input-group">
              <div class="input-group-prepend">
                <a class="input-group-text no-underline {{$piece->lookup('score_path')}}" href="{{storage($piece->score_path)}}" target="_blank"><i class="fas fa-file-alt"></i></a>
              </div>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="score" id="customFile">
                <label class="custom-file-label truncate" for="customFile">Score</label>
              </div>
            </div>
          </div>
          <div class="form-row form-group">
            <div class="col input-group">
              <div class="input-group-prepend">
                <a class="input-group-text no-underline {{$piece->lookup('audio_path_lh')}}" href="{{storage($piece->audio_path_lh)}}" target="_blank"><i class="fas fa-hand-paper" style="transform: scaleX(-1)"></i></a>
              </div>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="audio_lh" id="customFile">
                <label class="custom-file-label truncate" for="customFile">Left hand</label>
              </div>
            </div>
            <div class="col input-group">
              <div class="input-group-prepend">
                <a class="input-group-text no-underline {{$piece->lookup('audio_path_rh')}}" href="{{storage($piece->audio_path_rh)}}" target="_blank"><i class="fas fa-hand-paper"></i></a>
              </div>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="audio_rh" id="customFile">
                <label class="custom-file-label truncate" for="customFile">Right hand</label>
              </div>
            </div>
          </div>
          @endmanager
        </div>

        <div class="col-lg-6 col-sm-10 col-12 mx-auto">
            {{-- Tags --}}
            <div class="rounded bg-light px-3 py-2 mb-3">
              <p class="text-brand border-bottom pb-1 mb-1"><strong>TAGS</strong></p>
              <div class="d-flex flex-wrap">
                @foreach($types as $type => $tags)
                <label class="p-2 mb-1 text-center w-100"><strong>{{ucfirst($type)}}</strong></label>
                  @foreach($tags as $tag)
                  <div class="custom-control custom-checkbox mx-2 mb-2">
                    <input type="checkbox" class="custom-control-input" name="tags[]" value="{{$tag->id}}" id="{{$tag->name}}" {{($piece->tags->contains($tag->id)) ? 'checked' : ''}}>
                    <label class="custom-control-label" for="{{$tag->name}}">{{$tag->name}}</label>
                  </div>
                  @endforeach
                @endforeach
              </div>
              <div class="mb-1 mt-4 ml-2 text-muted">
                <small>Special tags are: {{\App\Tag::special()->get()->implode('name', ', ')}}</small>
              </div>
            </div>
            @manager
            {{-- iTunes --}}
            @component('admin.pages.pieces.itunes.layout')
              @if($piece->itunes_array)
                @foreach($piece->itunes_array as $itunes)
                @include('admin.pages.pieces.itunes.input', [
                  'names' => ["itunes[{$loop->index}][album]", "itunes[{$loop->index}][artist]", "itunes[{$loop->index}][link]"],
                  'album' => $itunes['album'],
                  'artist' => $itunes['artist'],
                  'link' => $itunes['link']])
                @endforeach
              @endif
            @endcomponent

            {{-- Youtube --}}
            @component('admin.pages.pieces.youtube.layout')
              @if($piece->youtube_array)
                @foreach($piece->youtube_array as $youtube)
                @include('admin.pages.pieces.youtube.input', [
                  'value' => $youtube,
                  'type' => 'd-flex',
                  'name' => 'youtube[]'])
                @endforeach
              @endif
            @endcomponent
            @endmanager
        </div>

        <div class="col-12 text-right">
            @can('update', $piece)
            <div class="">
              <button type="submit" class="btn btn-default">Save changes</button>
            </div>

            @endcan

            @include('admin.components.creator', ['model' => $piece, 'type' => 'piece'])
        </div>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')

@cannot('update', $piece)
<script type="text/javascript">
$('#edit-form input, #edit-form select, #edit-form textarea').attr('disabled', true);
$('.add-new-field, .remove-field').remove();
</script>
@endcannot
<script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>
<script type="text/javascript">
function showTooltip(element) {
    $(element).tooltip('show');

    setTimeout(function(){
        $(element).tooltip('hide');
    },1000);
}

$('[data-toggle="tooltip"]').tooltip();

var clipboard = new ClipboardJS('.clip');

clipboard.on('success', function(e) {
    showTooltip(e.trigger);
    e.clearSelection();
});

</script>

<script type="text/javascript">
/////////////////
// ADD NEW TIP //
/////////////////
$('a.add-new-field').on('click', function() {
  $button = $(this);
  $type = $button.attr('data-type');
  $clone = $button.siblings('.original-type').clone();

  if ($type == 'itunes') {
    number = $('.itunes-form:not(.original-type)').length;
    inputs = $clone.find('input');
    $(inputs[0]).attr('name',  'itunes['+number+'][album]');
    $(inputs[1]).attr('name',  'itunes['+number+'][artist]');
    $(inputs[2]).attr('name',  'itunes['+number+'][link]');
    $clone.removeClass('original-type').insertBefore($button).show();

  } else if ($type == 'youtube') {
    $clone.find('input').attr('name',  'youtube[]');
    $clone.removeClass('original-type').insertBefore($button).addClass('d-flex');

  }
});

////////////////
// REMOVE TIP //
////////////////
$(document).on('click', 'a.remove-field', function() {
  $(this).parent().remove();
});

/////////////////////////////
// HIGHLIGHT UPLOADED FILE //
/////////////////////////////
$('input[type="file"]').on('change', function(e) {
  $input = $(this);
  $label = $input.siblings('label');
  filename = e.target.files[0].name;

  $icon = $input.parent().siblings().find('i').addClass('text-success');
  $label.text(filename);
});
</script>
@endsection
