@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Email list',
    'description' => 'Manage the ' . $list->name . ' list'])
    
    @include('components.return', ['url' => route('admin.subscriptions.lists.index'), 'to' => 'email lists'])

    <div class="row mb-4">
      <div class="col-12">
        <form method="POST" action="{{route('admin.subscriptions.lists.update', $list)}}" class="form-row">
          @csrf
          @method('PATCH')
          <div class="col-lg-3 col-md-6 col-12">
            <div class="form-group">
              <input type="text" class="form-control" name="name" placeholder="List name" required value="{{$list->name}}">
            </div>
            <button type="submit" class="btn btn-default btn-block">Update list</button>
          </div>
          <div class="col-lg-9 col-md-6 col-12">
            <div class="form-group h-100">
              <textarea class="form-control h-100" name="description" required placeholder="Describe the list here">{{$list->description}}</textarea>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="row mb-2">
      <div class="col-12">
        <div class="alert alert-warning text-center"><i class="fas fa-users"></i> This list has a total of <strong>{{$list->subscribers_count}}</strong> {{str_plural('subscriber', $list->subscribers_count)}}</div>
      </div>
    </div>

    @datatable(['table' => 'subscriptions', 'columns' => ['Date', 'Email', 'Origin', 'Status']])

  </div>
</div>

@include('admin.components.modals.delete')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>

<script type="text/javascript">

(new DataTable('#subscriptions-table')).columns([
  {data: 'created_at', class: 'text-nowrap', sort: true},
  {data: 'email', name: 'subscriptions.email'},
  {data: 'origin_url', name: 'subscriptions.origin_url'},
  {data: 'status'}
]).create();
</script>

<script type="text/javascript">
$('#check-all-datatable').change(function() {
  $('.check-datatable').prop('checked', $(this).is(':checked'));
  getIds();
});

$('.check-datatable').on('change', function() {
  getIds();
});

function getIds()
{
  let ids = $('.check-datatable:checked').map(function() {
    return $(this).attr('data-id');
  }).toArray();
  console.log(ids);

  $('form#export-form input[name="ids"]').val(JSON.stringify(ids));  
}
</script>
@endsection