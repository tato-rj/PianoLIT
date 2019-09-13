@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Pianists',
    'description' => 'Edit a pianist'])

    <div class="row my-5 mx-2">
      <form id="edit-form" method="POST" action="{{route('admin.pianists.update', $pianist->id)}}" enctype="multipart/form-data" class="col-lg-6 col-sm-10 col-12 mx-auto">
        @csrf
        @method('PATCH')
        {{-- Name --}}
        <div class="form-group row">
          <label class="col-sm-2 col-form-label text-brand">Name</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" name="name" placeholder="Full name" value="{{ $pianist->name }}" required>
          </div>
        </div>
        {{-- Biography --}}
        <div class="form-group row">
          <label class="col-sm-2 col-form-label text-brand">Biography</label>
          <div class="col-sm-10">
            <textarea class="form-control" rows="6" name="biography" placeholder="pianist's biography" required>{{ $pianist->biography }}</textarea>
          </div>
        </div>
        <div class="form-group">
          <div class="w-100 d-flex justify-content-between">
            <label class="text-brand">iTunes ID</label>
            <div>@include('admin.components.link', ['link' => 'https://linkmaker.itunes.apple.com/en-us'])</div>
          </div>
          <input type="text" class="form-control" name="itunes_id" placeholder="iTunes ID" value="{{ $pianist->itunes_id }}">
        </div>
        {{-- Nationality and period --}}
        <div class="form-row form-group">
          <div class="col">
            <div class="form-group">
              <label class="text-brand">Nationality</label>
              <select class="form-control" name="country_id">
                <option selected disabled>Nationality</option>
                @foreach($countries as $country)
                <option value="{{$country->id}}" {{(strtolower($pianist->country_id) == $country->id) ? 'selected' : ''}}>{{$country->nationality}}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="text-brand">Born in</label>
              <input type="text" class="form-control" id="born-in" name="date_of_birth" placeholder="Born in" value="{{ $pianist->date_of_birth->format('m/d/Y') }}" required>      
            </div>
          </div>
          <div class="col">
            <div class="form-group">
              <label class="text-brand">Cover image</label>
              <div class="custom-file">
                <input type="file" class="custom-file-input" name="cover" id="customFile">
                <label class="custom-file-label truncate" for="customFile">Upload</label>
              </div>
            </div>
            <div class="form-group">
              <label class="text-brand">Died in</label>
              <input type="text" class="form-control" id="died-in" name="date_of_death" placeholder="Died in" value="{{ $pianist->date_of_death ? $pianist->date_of_death->format('m/d/Y') : null}}">
            </div>
          </div>
        </div>

        @can('update', $pianist)
        <div class="text-center mt-5">
          <button type="submit" class="btn btn-block btn-default">Save changes</button>
        </div>
        @endcan

        <div class="mt-3">
          <p class="text-muted text-right"><small><i>This pianist was created by <strong>{{$pianist->creator->name}}</strong></i></small></p>
        </div>
      </form>
      
      <div class="col-lg-6 col-sm-10 col-12 mx-auto">
        <img src="{{storage($pianist->cover_path)}}" class="shadow rounded-circle d-block mx-auto mb-4" style="width: 120px">
        <div>
          <div id="api-results" style="max-height: 460px; overflow-y: scroll;">
            <p class="text-muted text-center"><i>Loading iTunes albums...</i></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
$(document).ready(function() {
  $('#api-results').html('');
  $.ajax({
      url: 'https://itunes.apple.com/lookup',
      data: {id: '{{$pianist->itunes_id}}', entity: 'album'},
      type: 'GET',
      crossDomain: true,
      dataType: 'jsonp',
      success: function(response) { 
        let albums = response.results;
        albums.shift();
        let html = '<p class="text-muted">We found '+albums.length+' albums on iTunes</p>';
        // albums = albums.slice(0,10);

        for (album in albums) {
          html += `
            <div class="d-flex mb-2 pb-2 border-bottom">
              <div class="mr-3"><img src="`+albums[album].artworkUrl100+`"></div>
              <div>
                <a href="`+albums[album].collectionViewUrl+`" target="_blank"><p class="m-0">`+albums[album].collectionName+`</p></a>
                <p>Price: `+albums[album].collectionPrice+` `+albums[album].currency+`</p>
              </div>
            </div>
          `;
        }

        $('#api-results').html(html);
      },
      error: function(status) { alert('Couldn\'t get the albums from iTunes!'); }
  });
});
</script>
@cannot('update', $pianist)
<script type="text/javascript">
$('#edit-form input, #edit-form select, #edit-form textarea').attr('disabled', true);
$('.add-new-field').remove();
</script>
@endcannot

<script type="text/javascript">
function select()
{
  $form = $('#select-form');
  $slug = $('#select-form select option:selected').attr('data-slug');
  window.location = $form.attr('action')+$slug;
}

var bornIn = document.getElementById("born-in");
var diedIn = document.getElementById("died-in");

$(document).ready(function(){
  $(bornIn).inputmask("99/99/9999");
  $(diedIn).inputmask("99/99/9999");
});
</script>
@endsection
