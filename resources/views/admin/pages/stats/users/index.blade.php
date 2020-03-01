@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Statistics',
    'description' => 'Data analytics for the users'])

    <ul class="nav nav-pills justify-content-center mb-3" id="pills-tab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="pills-logs-tab" data-toggle="pill" href="#pills-logs" role="tab" aria-controls="pills-logs" aria-selected="true">Logs</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="pills-data-tab" data-toggle="pill" href="#pills-data" role="tab" aria-controls="pills-data" aria-selected="false">Data</a>
        </li>
    </ul>
    <div class="tab-content" id="pills-tabContent">
        <div class="tab-pane fade show active" id="pills-logs" role="tabpanel" aria-labelledby="pills-logs-tab">
            @include('admin.pages.stats.users.sections.logs')
        </div>
        <div class="tab-pane fade" id="pills-data" role="tabpanel" aria-labelledby="pills-data-tab">
            @include('admin.pages.stats.users.sections.data')
        </div>
    </div>

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTableRaw({table: '#users-table', options: {order: [[6, 'desc']]}})).create();
</script>

<script type="text/javascript">
var quickchart = new QuickChart;

$(document).ready(function() {
    // quickchart.setup({
    //   element: '#stats-logs', 
    //   url: "{{route('admin.stats.users', ['type' => 'logs'])}}"
    // }).make('line');

    quickchart.setup({
      element: '#stats-gender', 
      url: "{{route('admin.stats.users', ['type' => 'gender'])}}"
    }).make('pie');

    quickchart.setup({
      element: '#stats-confirmed', 
      url: "{{route('admin.stats.users', ['type' => 'confirmed'])}}"
    }).make('pie');

    quickchart.setup({
      element: '#stats-favorites', 
      url: "{{route('admin.stats.users', ['type' => 'favorites'])}}"
    }).make('pie');

    quickchart.setup({
      element: '#stats-signups', 
      url: "{{route('admin.stats.users', ['type' => 'daily'])}}"
    }).make('line');
});
</script>
@endsection
