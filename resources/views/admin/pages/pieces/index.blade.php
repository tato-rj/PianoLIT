@extends('admin.layouts.app')

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Pieces',
    'description' => 'Manage the pieces'])

    <div class="row">
      <div class="col-12 d-flex justify-content-between">
        <div>
          <a href="{{route('admin.pieces.create')}}" class="btn btn-sm btn-default">
            <i class="fas fa-plus mr-2"></i>Add a new piece
          </a>
        </div>
        <div>
          @include('admin.components.filters', ['filters' => ['composers', 'levels', 'periods', 'lengths']])
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-12 text-center">
        <p class="text-center"><small>Showing {{$pieces->count()}} of {{$pieces->total()}} pieces</small></p>
      </div>
      @foreach($pieces as $piece)
      @include('admin.pages.pieces.row')
      @endforeach
    </div>

    {{-- PAGINATION --}}
    <div class="row mb-3">
          <div class="d-flex align-items-center w-100 justify-content-center my-4">
        {{ $pieces->links() }}    
        </div>
    </div>

  </div>
</div>

@include('admin.components.modals.delete', ['model' => 'piece'])

@endsection

@section('scripts')
<script type="text/javascript">
$('.delete').on('click', function (e) {
  $piece = $(this);
  name = $piece.attr('data-name');
  url = $piece.attr('data-url');
  $('#delete-modal').find('form').attr('action', url);
})
</script>
@endsection