@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Pieces',
    'description' => 'Add a new piece'])

    <div class="row mb-3">
      <div class="col-12">
        <div class="form-inline justify-content-end">
          <div class="btn-group btn-group-toggle mr-2" title="Show composers that need more pieces">
            <label class="btn btn-light">
              <input type="checkbox" name="alerts[]" autocomplete="off" value="composers"><i class="fas fa-user"></i>
            </label>
          </div>
          <div class="btn-group btn-group-toggle mr-2" title="Show levels that need more pieces">
            <label class="btn btn-light">
              <input type="checkbox" name="alerts[]" autocomplete="off" value="levels"><i class="fas fa-swimmer"></i></i>
            </label>
          </div>
          <div class="btn-group btn-group-toggle" title="Show periods that need more pieces">
            <label class="btn btn-light">
              <input type="checkbox" name="alerts[]" autocomplete="off" value="periods"><i class="fas fa-monument"></i></i>
            </label>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      
      <div class="col-12" id="alerts-container"></div>

      <div class="col-lg-6 col-md-8 col-12">
        <form method="POST" id="create-piece" action="{{route('admin.pieces.store')}}" autocomplete="off" enctype="multipart/form-data">
          @csrf
          {{-- Name --}}
          <div class="form-group form-row">
            <div class="col">
              <input type="text" class="validate-name form-control required {{$errors->has('name') ? 'is-invalid' : ''}}" name="name" placeholder="Piece name" value="{{ old('name') }}" >
              @include('admin.components.feedback', ['field' => 'name'])
            </div>
            <div class="col">
              <input type="text" class="form-control" name="nickname" placeholder="Nickname" value="{{ old('nickname') }}" >
            </div>
          </div>
          <div class="form-group form-row">
            <div class="col">
              <input type="text" class="validate-name form-control" name="collection_name" placeholder="Collection name" value="{{ old('collection_name') }}">
            </div>
            <div class="col">
              <select class="form-control required {{$errors->has('composer_id') ? 'is-invalid' : ''}}" name="composer_id">
                <option class="default" selected disabled>Composer</option>
                @foreach($composers as $composer)
                <option value="{{$composer->id}}" {{ old('composer_id') == $composer->id ? 'selected' : ''}}>{{$composer->short_name}}</option>
                @endforeach
              </select>
              @include('admin.components.feedback', ['field' => 'composer_id'])
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
                <input type="text" class="validate-name form-control" name="collection_number" placeholder="Number" value="{{ old('collection_number') }}">
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
                <input type="text" class="validate-name form-control" name="movement_number" placeholder="Number" value="{{ old('movement_number') }}">
              </div>
            </div>
            <div class="col">
              <label class="text-brand"><small>Catalogue number
                @include('admin.components.info', ['message' => 'This number will appear after the name of the piece: <i>Piece Name</i> <u>Op. 1</u>'])
              </small></label>
              <div class="input-group">
                <div class="input-group-prepend" style="width: 40%">
                  <select class="form-control rounded-left" style="border-radius: 0" name="catalogue_name" >
                    <option class="default" selected disabled>Cat.</option>
                    @foreach(catalogues() as $catalogue)
                    <option value="{{$catalogue}}" {{ old('catalogue_name') == $catalogue ? 'selected' : ''}}>{{$catalogue}}</option>
                    @endforeach
                    <option value="">None</option>
                  </select>
                </div>
                <input type="text" class="validate-name form-control" name="catalogue_number" placeholder="Number" value="{{ old('catalogue_number') }}">
              </div>
            </div>
          </div>
          {{-- Key and Composer --}}
          <div class="form-row form-group">
            <div class="col">
              <input type="number" min="1600" max="{{now()->year}}" class="form-control" name="composed_in" placeholder="Composed in" value="{{old('composed_in')}}">
              @include('admin.components.feedback', ['field' => 'composed_in'])
            </div>
            <div class="col">
              <input type="number" min="1600" max="{{now()->year}}" class="form-control" name="published_in" placeholder="Published in" value="{{old('published_in')}}">
              @include('admin.components.feedback', ['field' => 'published_in'])
            </div>
            <div class="col">
              <select class="form-control required {{$errors->has('key') ? 'is-invalid' : ''}}" name="key" >
                <option class="default" selected disabled>Key</option>
                <optgroup label="Tonal">
                  @foreach(keys() as $key)
                  <option value="{{$key}}" {{ old('key') == $key ? 'selected' : ''}}>{{$key}}</option>
                  @endforeach
                </optgroup>
                <optgroup label="Non-tonal">
                  <option value="Atonal" {{ old('key') == 'Atonal' ? 'selected' : ''}}>Atonal</option>
                  <option value="Modal" {{ old('key') == 'Modal' ? 'selected' : ''}}>Modal</option>
                  <option value="Serial" {{ old('key') == 'Serial' ? 'selected' : ''}}>Serial</option>
                  <option value="Chromatic" {{ old('key') == 'Chromatic' ? 'selected' : ''}}>Chromatic</option>
                  <option value="Experimental" {{ old('key') == 'Experimental' ? 'selected' : ''}}>Experimental</option>
                </optgroup>
              </select>
              @include('admin.components.feedback', ['field' => 'key'])
            </div>
          </div>
          {{-- Period, Length and Level --}}
          <div class="form-row form-group">
            <div class="col">
              <select class="form-control required {{$errors->has('period') ? 'is-invalid' : ''}}" name="period[]" >
                <option class="default" selected disabled>Period</option>
                @foreach(\App\Tag::periods()->get() as $period)
                <option value="{{$period->id}}" {{ old('period') == $period->id ? 'selected' : ''}}>{{ucfirst($period->name)}}</option>
                @endforeach
              </select>
              @include('admin.components.feedback', ['field' => 'period'])
            </div>
            <div class="col">
              <select class="form-control required {{$errors->has('length') ? 'is-invalid' : ''}}" name="length[]">
                <option class="default" selected disabled>Length</option>
                @foreach(\App\Tag::lengths()->get() as $length)
                <option value="{{$length->id}}" {{ old('length') == $length->id ? 'selected' : ''}}>{{ucfirst($length->name)}}</option>
                @endforeach
              </select>
              @include('admin.components.feedback', ['field' => 'length'])
            </div>
            <div class="col">
              <select class="form-control required {{$errors->has('level') ? 'is-invalid' : ''}}" name="level[]" >
                <option class="default" selected disabled>Level</option>
                @foreach(\App\Tag::levels()->get() as $level)
                <option value="{{$level->id}}" {{ old('level') == $level->id ? 'selected' : ''}}>{{ucfirst($level->name)}}</option>
                @endforeach
              </select>
              @include('admin.components.feedback', ['field' => 'level'])
            </div>
          </div>
          {{-- Did you know? --}}
          <div class="form-group">
            <textarea class="form-control" rows="3" name="curiosity" placeholder="Enter a curiosity here">{{old('score_editor')}}</textarea>
          </div>
          @manager
          {{-- Score Info --}}
          <div class="bg-light rounded px-3 py-2 mb-3">
            <div class="d-flex justify-content-between">
              <div>Is this piece in public domain?</div>
              @include('admin.components.toggle.copyright', ['is_public' => true])
            </div>
            <div class="is-public mt-3">
              <div class="form-group">
                <input type="text" class="form-control" name="score_editor" placeholder="Score editor" value="{{old('score_editor')}}">
              </div>
              <div class="form-group">
                <input type="text" class="form-control" name="score_publisher" placeholder="Score publisher" value="{{ old('score_publisher') }}">
                @include('admin.components.lookup')
              </div>
            </div>
            <div class="form-group non-public mt-3" style="display: none;">
              <input type="text" class="form-control" name="score_url" maxlength="255" placeholder="Score url" value="{{ old('score_url') }}">
            </div>
          </div>
          {{-- Files --}}
          <div class="form-row form-group">
            <div class="col input-group">
              <div class="input-group-prepend">
                <div class="input-group-text"><i class="fas fa-microphone"></i></div>
              </div>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="audio" id="customFile">
                <label class="custom-file-label truncate" for="customFile">Both hands</label>
              </div>
            </div>
            <div class="col input-group">
              <div class="input-group-prepend">
                <div class="input-group-text"><i class="fas fa-file-alt"></i></div>
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
                <div class="input-group-text"><i class="fas fa-hand-paper" style="transform: scaleX(-1)"></i></div>
              </div>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="audio_lh" id="customFile">
                <label class="custom-file-label truncate" for="customFile">Left hand audio</label>
              </div>
            </div>
            <div class="col input-group">
              <div class="input-group-prepend">
                <div class="input-group-text"><i class="fas fa-hand-paper"></i></div>
              </div>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="audio_rh" id="customFile">
                <label class="custom-file-label truncate" for="customFile">Right hand audio</label>
              </div>
            </div>
          </div>
  {{--         <div class="form-group">
            <select class="form-control {{$errors->has('performer_id') ? 'is-invalid' : ''}}" name="performer_id" >
              <option class="default" selected disabled>Performer</option>
              <option>PianoLIT</option>
            </select>
            @include('admin.components.feedback', ['field' => 'key'])
          </div> --}}
          @endmanager
          {{-- Tags --}}
          <hr class="my-4">
          <div class="rounded bg-light px-3 py-2 mb-3">
            <p class="text-brand border-bottom pb-1 mb-1"><strong>TAGS</strong></p>
            <div class="d-flex flex-wrap">
              @foreach($types as $type => $tags)
              <label class="p-2 mb-1 text-center w-100"><strong>{{ucfirst($type)}}</strong></label>
                @foreach($tags as $tag)
                <div class="custom-control custom-checkbox mx-2 mb-2">
                  <input type="checkbox" class="custom-control-input tag-input" name="tags[]" value="{{$tag->id}}" id="{{$tag->name}}">
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
          @include('admin.pages.pieces.itunes.layout')
          {{-- Videos --}}
          @include('admin.pages.pieces.videos.layout', ['subject' => 'VIDEOS'])
          @endmanager

          <div class="text-center my-5">
            <button type="submit" id="submit-button" class="btn btn-block btn-default">Add piece</button>
          </div>
        </form>
      </div>
      <div class="col-lg-6 col-md-8 col-12">
        <div class="">
          <div class="mb-4 pb-3 border-bottom">
            <p class="text-muted m-0"><strong>From the database</strong></p>
          </div>
          <div id="validation-results">
            <p class="text-muted text-center mb-0 empty"><small><i>Start typing to view similar pieces here...</i></small></p>
            <div class="results row">
              
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="{{asset('js/vendor/jquery.ba-throttle-debounce.min.js')}}"></script>
<script type="text/javascript" src="{{asset('js/vendor/lookup.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/clipboard@2/dist/clipboard.min.js"></script>
<script type="text/javascript">
// $(document).on('click', '.youtube-to-mp3', function(event) {
//   event.preventDefault();
//   let $modal = $('#modal-youtube-to-mp3');
//   let id = $(this).parent().siblings('input').val();
//   if (id) {
//     window.open("https://www.yt-download.org/@api/button/mp3/"+id, "_blank");
//   } else {
//     alert('You forgot to include the ID!');
//   }
// });
</script>
<script type="text/javascript">
  $('input[name="alerts[]"]').on('click', function() {
    let alerts = [];
    let $alertsContainer = $('#alerts-container');
    
    $(this).parent().toggleClass('active');
    
    $alertsContainer.html(`<p class="text-muted text-center mb-4"><i>Loading...</i></p>`);

    $.each($('input[name="alerts[]"]:checked'), function(){            
      alerts.push($(this).val());
    });
    if (alerts.length == 0) {
      $alertsContainer.html('');
    } else {
      $.get("{{route('admin.pieces.alerts')}}", {alerts: alerts}, function(data) {
        $alertsContainer.html(data);
      }).fail(function(response) {
        console.log(response);
      });
    }
  });

$(document).on('blur', 'input.itunes-link', function() {
  let url = $(this).val();
  let updatedUrl = url.replace('https', 'itms');
  $(this).val(updatedUrl);
});

$('.status-toggle').on('change', function() {
  if ($(this).is(':checked')) {
    $('.is-public').show();
    $('.non-public').hide();
  } else {
    $('.is-public').hide();
    $('.non-public').show();
  }
});

$('.tag-input').on('change', function() {
  let tags = $('.tag-input:checked').length;

  if (tags > 7)
    alert('You are adding too many tags! Try to keep them between 5 and 7 :)');
});

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

new Lukup({
  url: app.url+'/admin/pieces/single-lookup',
  field: 'score_publisher',
  autofill: ['score_publisher']
}).enable();

new Lukup({
  url: app.url+'/admin/pieces/single-lookup',
  field: 'score_editor',
  autofill: ['score_editor']
}).enable();

new Lukup({
  url: app.url+'/admin/pieces/single-lookup',
  field: 'score_copyright',
  autofill: ['score_copyright']
}).enable();

new Lukup({
  url: app.url+'/admin/pieces/single-lookup',
  field: 'nickname',
  autofill: ['nickname']
}).enable();

new Lukup({
  url: app.url+'/admin/pieces/single-lookup',
  field: 'name',
  autofill: ['name']
}).enable();

new Lukup({
  url: app.url+'/admin/pieces/multi-lookup',
  field: 'collection_name',
  autofill: ['collection_name', 'nickname', 'score_url', 'catalogue_name', 'catalogue_number', 'composer_id', 'composed_in', 'published_in', 'score_editor', 'score_copyright', 'score_publisher', 'curiosity'],
  exclude: ['No information available']
}).enable();

</script>

<script type="text/javascript">

$('input.validate-name').on('blur', function() {

  setTimeout(function(){
    input = {};
    $('.validate-name').each(function() {
      input[$(this).attr('name')] = $(this).val();
    });

    $.post(app.url+'/admin/pieces/validate-name', input, function(data, status, xhr) {

      $('#validation-results .results').html('');

      if (data != '') {
        $('#validation-results .empty').hide();

        $('#validation-results .results').append(data);
      } else {
        $('#validation-results .empty').show();
      }
    }).fail(function(error) {
      $('#validation-results .results').html('');
      $('#validation-results .empty').show();
    });
  }, 500);

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

  } else if ($type == 'videos') {
    $clone.find('input').attr('name',  'videos[]');
    $clone.removeClass('original-type').insertBefore($button).addClass('d-flex');

  } else {
    $clone.find('textarea').attr('name',  'tips[]');
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

  $icon = $input.parent().siblings().find('> div').addClass('text-success');
  $label.text(filename);
});
</script>

<script type="text/javascript">
$('body').on('paste', '.itunes-link', function() {
  window.setTimeout(() => {
    var newUrl = $(this).val().replace('https', 'itms');
    $(this).val(newUrl);
  });
    
});
</script>

<script type="text/javascript">
function validateForm() {
  var isValid = true;

  $('input.required').each(function() {
    if ( $(this).val() === '' )
        isValid = false;
  });

  $('select.required').each(function() {
    if ($('option:selected', this).hasClass('default'))
        isValid = false;    
  });

  return isValid;
}

function nameToString(name) {
  name = name.replace('[]', '');
  name = name.replace('_id', '');
  name = name.replace('_', ' ');
  return name;
}

function arrayToString(array) {
  return array.join(', ');
}

function showAlert() {
    let names = [];
    $('.required').each(function() {
      names.push(nameToString($(this).attr('name')));
    })
    alert('The following fields are required: ' + arrayToString(names));
}

$('#submit-button').on('click', function(event) {
  event.preventDefault();
  if (validateForm()) {
    $('#create-piece').submit();
  } else {
    showAlert();
  }
});
</script>
@endsection
