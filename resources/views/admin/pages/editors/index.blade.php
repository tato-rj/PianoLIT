@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Editors',
    'description' => 'Manage the editors'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <button type="button" class="btn btn-sm btn-default" data-toggle="modal" data-target="#add-modal">
            <i class="fas fa-plus mr-2"></i>Add a new editor
          </button>
        </div>
        <div>
          {{-- @include('admin.components.filters', ['filters' => []]) --}}
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12 text-center">
        <p class="text-center"><small>Showing {{$editors->count()}} of {{$editors->total()}}</small></p>
      </div>
      @foreach($editors as $editor)
      <div class="col-12 mb-2">
        <div class="d-flex justify-content-between px-3 py-2 bg-light text-muted rounded">
          <div>
            <strong>{{$editor->name}} | <small>{{$editor->pieces_count}} {{str_plural('piece', $editor->pieces_count)}} - joined on {{$editor->created_at->toFormattedDateString()}}</small></strong>
          </div>
          <div class="text-right text-brand">
            <a href="{{route('admin.editors.edit', $editor->id)}}">edit</a> | <a href="" data-name="{{$editor->name}}" data-url="{{route('admin.editors.destroy', $editor->id)}}" data-toggle="modal" data-target="#delete-modal" class="delete">delete</a>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- PAGINATION --}}
    <div class="row mb-3">
          <div class="d-flex align-items-center w-100 justify-content-center my-4">
        {{ $editors->links() }}    
        </div>
    </div>

  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'editor'])

@component('admin.components.modals.add', ['model' => 'editor'])
<form method="POST" action="{{route('admin.editors.store')}}">
  {{csrf_field()}}
  {{-- Name --}}
  <div class="form-group">
    <input type="text" class="form-control" name="name" placeholder="Full name" value="{{ old('name') }}" required>
    @include('admin.components.feedback', ['field' => 'name'])
  </div>
  {{-- Email --}}
  <div class="form-group">
    <input type="text" class="form-control" name="email" placeholder="E-mail" value="{{ old('email') }}" required>
    @include('admin.components.feedback', ['field' => 'email'])
  </div>
  {{-- Password --}}
  <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
    <input id="password" type="password" placeholder="Password" class="form-control" name="password" required>
    @include('admin.components.feedback', ['field' => 'password'])
  </div>
  <div class="form-group">
    <input id="password-confirm" type="password" placeholder="Confirm your password" class="form-control" name="password_confirmation" required>
  </div>

@endcomponent

@endsection

@section('scripts')
<script type="text/javascript">
$('.delete').on('click', function (e) {
  $editor = $(this);
  name = $editor.attr('data-name');
  url = $editor.attr('data-url');
  $('#delete-modal').find('form').attr('action', url);
})
</script>
@endsection