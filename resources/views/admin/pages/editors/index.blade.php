@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'title' => 'Editors',
      'subtitle' => 'Manage the people who help build the PianoLIT library.',
      'action' => ['label' => 'Add a new editor', 'modal' => 'add-modal']
    ])

    <div class="row my-3">
      @foreach($editors as $editor)
      <div class="col-12 mb-2">
        <div class="d-flex justify-content-between px-3 py-2 bg-light text-muted rounded">
          <div>
            <strong>{{$editor->name}} | <small>{{$editor->pieces_count}} {{str_plural('piece', $editor->pieces_count)}} - joined on {{$editor->created_at->toFormattedDateString()}}</small></strong>
          </div>
          <div class="d-flex flex-nowrap align-items-center gap-2">
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.editors.edit', $editor->id)]])
            @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-name' => $editor->name, 'data-url' => route('admin.editors.destroy', $editor->id), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal']]])
          </div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- PAGINATION --}}
    @include('admin.components.pagination', ['paginator' => $editors, 'label' => 'Editors'])

  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'editor'])

@component('components.modal', ['id' => 'add-modal', 'header' => 'New editor'])
@slot('body')
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
  @submit(['label' => 'Add editor', 'block' => true])
</form>
@endslot
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
