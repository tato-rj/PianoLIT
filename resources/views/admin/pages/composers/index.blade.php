@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Composers',
    'description' => 'Manage the composers'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center mb-4">
        <div>
          <button type="button" class="btn btn-sm btn-default" data-toggle="modal" data-target="#add-modal">
            <i class="fas fa-plus mr-2"></i>Add a new composer
          </button>
        </div>
        <div>
          <button type="button" data-toggle="modal" data-target="#famous-birthdays" class="btn btn-sm btn-warning">
            <i class="fas fa-birthday-cake mr-2"></i>Famous birthdays
          </button>
        </div>
      </div>
    </div>

    @datatable(['table' => 'composers', 'columns' => ['Name', 'Famous', 'Pieces count', '']])

  </div>
</div>

@include('admin.components.modals.delete')
@include('admin.pages.composers.birthdays')

@component('admin.components.modals.add', ['model' => 'composer'])
<form method="POST" action="{{route('admin.composers.store')}}" enctype="multipart/form-data">
  @csrf
  {{-- Name --}}
  <div class="d-flex form-group">
    <div class="flex-grow mr-2">
      <input type="text" class="form-control" name="name" placeholder="Full name" value="{{ old('name') }}" required>
    </div>
    <div>
      @include('admin.pages.composers.gender', ['gender' => old('gender')])
    </div>
  </div>
  {{-- Life --}}
  <div class="form-group">
    <textarea class="form-control" rows="6" name="biography" placeholder="Life's summary" required>{{ old('biography') }}</textarea>
  </div>
  {{-- Curiosity --}}
  <div class="form-group">
    <textarea class="form-control" rows="3" name="curiosity" placeholder="Did you know?">{{ old('curiosity') }}</textarea>
  </div>
  <div class="form-group">
    <div class="custom-file">
      <input type="file" class="custom-file-input" name="cover" id="customFile">
      <label class="custom-file-label truncate" for="customFile">Cover image</label>
    </div>
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
        @foreach(\App\Tag::periods()->get() as $period)
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
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">
var bornIn = document.getElementById("born-in");
var diedIn = document.getElementById("died-in");

$(bornIn).inputmask("99/99/9999");
$(diedIn).inputmask("99/99/9999");

(new DataTable('#composers-table')).columns([
  {data: 'name', class: 'dataTables_main_column'},
  {data: 'famous'},
  {data: 'pieces_count'},
  {data: 'actions', orderable: false, searchable: false},
]).create();
</script>
@endsection