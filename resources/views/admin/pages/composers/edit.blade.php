@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Composers',
    'description' => 'Edit a composer'])

    <div class="row my-5 mx-2">
      <form id="edit-form" method="POST" action="{{route('admin.composers.update', $composer->id)}}" enctype="multipart/form-data" class="col-lg-6 col-sm-10 col-12 mx-auto">
        @csrf
        @method('PATCH')
        {{-- Name --}}
        <div class="form-group row">
          <label class="col-sm-2 col-form-label text-brand">Name</label>
          <div class="col-sm-10">
            <div class="d-flex">
              <input type="text" class="form-control mr-2" name="name" placeholder="Full name" value="{{ $composer->name }}" required>
              @include('admin.pages.composers.gender', ['gender' => $composer->gender])
            </div>
          </div>
        </div>
        {{-- Biography --}}
        <div class="form-group row">
          <label class="col-sm-2 col-form-label text-brand">Biography</label>
          <div class="col-sm-10">
            <textarea class="form-control" rows="6" name="biography" placeholder="Composer's biography" required>{{ $composer->biography }}</textarea>
          </div>
        </div>
        {{-- Did you know? --}}
        <div class="form-group row">
          <label class="col-sm-2 col-form-label text-brand">Did you know?</label>
          <div class="col-sm-10">
            <textarea class="form-control" rows="3" name="curiosity" placeholder="Enter a curiosity here">{{ $composer->curiosity }}</textarea>
          </div>
        </div>

        <div class="form-group">
          <label class="text-brand">Cover image</label>
          <div class="custom-file">
            <input type="file" class="custom-file-input" name="cover" id="customFile">
            <label class="custom-file-label truncate" for="customFile">Upload</label>
          </div>
        </div>
        {{-- Nationality and period --}}
        <div class="form-row form-group">
          <div class="col">
            <div class="form-group">
              <label class="text-brand">Nationality</label>
              <select class="form-control" name="country_id">
                <option selected disabled>Nationality</option>
                @foreach($countries as $country)
                <option value="{{$country->id}}" {{(strtolower($composer->country_id) == $country->id) ? 'selected' : ''}}>{{$country->nationality}}</option>
                @endforeach
              </select>
            </div>
            <div class="form-group">
              <label class="text-brand">Period</label>
              <select class="form-control" name="period">
                <option selected disabled>Period</option>
                @foreach(\App\Tag::periods()->get() as $period)
                <option value="{{$period->name}}" {{(strtolower($composer->period) == $period->name) ? 'selected' : ''}}>{{ucfirst($period->name)}}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="col">
            <div class="form-group">
              <label class="text-brand">Born in</label>
              <input type="text" class="form-control" id="born-in" name="date_of_birth" placeholder="Born in" value="{{ is_object($composer->date_of_birth) ? $composer->date_of_birth->format('m/d/Y') : null }}">      
            </div>
            <div class="form-group">
              <label class="text-brand">Died in</label>
              <input type="text" class="form-control" id="died-in" name="date_of_death" placeholder="Died in" value="{{ is_object($composer->date_of_death) ? $composer->date_of_death->format('m/d/Y') : null}}">
            </div>
          </div>
        </div>

        @can('update', $composer)
        <div class="text-center mt-5">
          <button type="submit" class="btn btn-block btn-default">Save changes</button>
        </div>
        @endcan

        <div class="mt-3">
          <p class="text-muted text-right"><small><i>This composer was created by <strong>{{$composer->creator->name}}</strong></i></small></p>
        </div>
      </form>
      
      <div class="col-lg-6 col-sm-10 col-12 mx-auto">
        <div>
          <img src="{{storage($composer->cover_path)}}" class="rounded-circle shadow mb-4 mx-auto d-block" style="width: 160px">
        </div>
        <p class="text-muted"><strong>{{$composer->name}} has {{$composer->pieces_count}} {{str_plural('piece', $composer->pieces_count)}}</strong></p>
        @if($composer->pieces_count > 0)
        <ul class="list-style-none pl-2">
          @foreach($composer->pieces as $piece)
          <li class="mb-2">
            <a href="{{route('admin.pieces.edit', $piece)}}">
              <i class="fas fa-caret-right mr-2"></i>{{$piece->long_name}}
            </a>
          </li>
          @endforeach
        </ul>
        @endif
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
@cannot('update', $composer)
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
