@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Composers',
    'description' => 'Manage the composers'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <button type="button" class="btn btn-sm btn-default" data-toggle="modal" data-target="#add-modal">
            <i class="fas fa-plus mr-2"></i>Add a new composer
          </button>
        </div>
        <div>
          @include('admin.components.filters', ['filters' => []])
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12 text-center">
        <p class="text-center"><small>Showing {{$composers->count()}} of {{$composers->total()}}</small></p>
      </div>
      @foreach($composers as $composer)
      <div class="col-12 mb-2">
        <div class="d-flex justify-content-between px-3 py-2 bg-light text-muted rounded">
          <div>
            <strong>{{$composer->name}} ({{$composer->alive_on}})</strong> | <small>{{$composer->pieces_count}} {{str_plural('piece', $composer->pieces_count)}}</small>
          </div>
          <div class="text-right text-brand">
            @can('update', $composer)
            <a href="{{route('admin.composers.edit', $composer->id)}}">edit</a> | <a href="" data-name="{{$composer->name}}" data-url="{{route('admin.composers.edit', $composer->id)}}" data-toggle="modal" data-target="#delete-modal" class="delete">delete</a>
            @else
            <a href="{{route('admin.composers.edit', $composer->id)}}">view details</a>
            @endcan
          </div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- PAGINATION --}}
    <div class="row mb-3">
          <div class="d-flex align-items-center w-100 justify-content-center my-4">
        {{ $composers->links() }}    
        </div>
    </div>

  </div>
</div>

@include('admin.components.modals/delete', ['model' => 'composer'])

@component('admin.components.modals/add', ['model' => 'composer'])
<form method="POST" action="{{route('admin.composers.store')}}">
  @csrf
  {{-- Name --}}
  <div class="form-group">
    <input type="text" class="form-control" name="name" placeholder="Full name" value="{{ old('name') }}" required>
  </div>
  {{-- Life --}}
  <div class="form-group">
    <textarea class="form-control" rows="6" name="biography" placeholder="Life's summary" required>{{ old('biography') }}</textarea>
  </div>
  {{-- Curiosity --}}
  <div class="form-group">
    <textarea class="form-control" rows="3" name="curiosity" placeholder="Did you know?">{{ old('curiosity') }}</textarea>
  </div>
  {{-- Nationality and period --}}
  <div class="form-row form-group">
    <div class="col">
      <select class="form-control {{$errors->has('country_id') ? 'is-invalid' : ''}}" name="country_id">
        <option selected disabled>Nationality</option>
        @foreach($countries as $country)
        <option value="{{$country->id}}" {{ old('country_id') == $country->id ? 'selected' : ''}}>{{$country->name}}</option>
        @endforeach
      </select>
      @include('admin.components.feedback', ['field' => 'nationality'])
    </div>
    <div class="col">
      <select class="form-control {{$errors->has('period') ? 'is-invalid' : ''}}" name="period" >
        <option selected disabled>Period</option>
        @foreach(\App\Tag::periods() as $period)
        <option value="{{$period->name}}" {{ old('period') == $period->name ? 'selected' : ''}}>{{ucfirst($period->name)}}</option>
        @endforeach
      </select>
      @include('admin.components.feedback', ['field' => 'period'])
    </div>
  </div>
  {{-- Dates --}}
  <div class="form-row form-group">
    <div class="col">
      <input class="form-control {{$errors->has('date_of_birth') ? 'is-invalid' : ''}}" id="born-in" type="text" name="date_of_birth" placeholder="Date born" value="{{ old('date_of_birth') }}">
    </div>
    <div class="col">
      <input class="form-control {{$errors->has('date_of_death') ? 'is-invalid' : ''}}" id="died-in" type="text" name="date_of_death" placeholder="Date died" value="{{ old('date_of_death') }}">
    </div>
  </div>

@endcomponent

@endsection

@section('scripts')
<script src="{{asset('js/vendor/inputmask.bundle.js')}}"></script>

<script type="text/javascript">
$('.delete').on('click', function (e) {
  $composer = $(this);
  name = $composer.attr('data-name');
  url = $composer.attr('data-url');
  $('#delete-modal').find('form').attr('action', url);
})

var bornIn = document.getElementById("born-in");
var diedIn = document.getElementById("died-in");

$(document).ready(function(){
  $(bornIn).inputmask("99/99/9999");
  $(diedIn).inputmask("99/99/9999");
});
</script>
@endsection