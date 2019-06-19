@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Pianists',
    'description' => 'Manage the pianists'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <button type="button" class="btn btn-sm btn-default" data-toggle="modal" data-target="#add-modal">
            <i class="fas fa-plus mr-2"></i>Add a new pianist
          </button>
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12">
        <table class="table table-hover" id="pianists-table">
          <thead>
            <tr>
              <th class="border-0" scope="col">Name</th>
              <th class="border-0" scope="col">Nationality</th>
              <th class="border-0" scope="col"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($pianists as $pianist)
            @include('admin.pages.pianists.row')
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('admin.components.modals/delete', ['model' => 'pianist'])

@component('admin.components.modals/add', ['model' => 'pianist'])
<form method="POST" action="{{route('admin.pianists.store')}}">
  @csrf
  {{-- Name --}}
  <div class="form-group">
    <input type="text" class="form-control" name="name" placeholder="Full name" value="{{ old('name') }}" required>
  </div>
  {{-- Life --}}
  <div class="form-group">
    <textarea class="form-control" rows="6" name="biography" placeholder="Biography" required>{{ old('biography') }}</textarea>
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
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="itunes_id" placeholder="iTunes ID" value="{{ old('itunes_id') }}">
    <div class="ml-2">@include('admin.components.link', ['link' => 'https://linkmaker.itunes.apple.com/en-us'])</div>
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
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
$('.delete').on('click', function (e) {
  $pianist = $(this);
  name = $pianist.attr('data-name');
  url = $pianist.attr('data-url');
  $('#delete-modal').find('form').attr('action', url);
})

var bornIn = document.getElementById("born-in");
var diedIn = document.getElementById("died-in");

$(document).ready(function(){
  $('#pianists-table').DataTable({
    'aaSorting': [],
    'columnDefs': [ { 'orderable': false, 'targets': [2] } ],

  });
  $(bornIn).inputmask("99/99/9999");
  $(diedIn).inputmask("99/99/9999");
});
</script>
@endsection