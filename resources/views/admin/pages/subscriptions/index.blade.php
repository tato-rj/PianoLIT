@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Subscriptions',
    'description' => 'Manage the subscription list'])

    <div class="row">
      <div class="col-12 mb-4">
        <div class="mb-4">
        @include('admin.pages.subscriptions.create')
        </div>
        <div>
          <a href="{{route('admin.subscriptions.export', ['type' => 'txt'])}}" target="_blank" class="btn btn-light" name="export-list" data-type="text"><i class="fas fa-file-alt"></i></a>
        </div>
      </div>
    </div>

    @datatable(['model' => 'subscriptions', 'columns' => ['Date', 'Email', 'Origin', 'Newsletter', 'Birthday', '']])

  </div>
</div>

@include('admin.components.modals.delete')

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTable({table: '#subscriptions-table'})).create();
</script>
@endsection