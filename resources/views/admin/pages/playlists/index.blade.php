@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    <div class="row mb-3">
      <div class="col-12">
        @include('admin.pages.playlists.create')
      </div>
    </div>

    <div class="row">
      <div class="col-12 mb-4">
        <button data-toggle="modal" data-target="#playlists-overview" class="btn btn-sm btn-warning">Overview</button>
      </div>
    </div>

    @datatableRaw(['model' => 'playlists', 'columns' => ['Date', 'Name', 'Group', 'Number of pieces', '']])

  </div>
</div>

@include('admin.pages.playlists.overview')
@include('admin.components.modals.delete')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTableRaw({table: '#playlists-table'})).create();
</script>
@endsection