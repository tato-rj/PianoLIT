@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    <div class="row mb-4">
      <div class="col-12">
        @chart([
          'url' => route('admin.stats.users'),
          'chart' => 'line',
          'type' => 'logs',
          'title' => 'Activity logs',
          'subtitle' => 'Number of logs per day',
          'select' => [
            'logs_limit' => [
              ['label' => 'past 7 days', 'value' => 7],
              ['label' => 'past 14 days', 'value' => 14],
              ['label' => 'past 21 days', 'value' => 21],
              ['label' => 'past 28 days', 'value' => 28]
            ]
          ]
        ])
      </div>
    </div>

    @datatableRaw(['model' => 'users', 'rows' => 'admin.pages.stats.users.row', 'columns' => ['Date', 'Name', 'Visits', 'Favorites', 'Origin', 'Status', 'Last Active', '']])

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
    quickchart.setup({
      element: '#stats-logs', 
      url: "{{route('admin.stats.users', ['type' => 'logs'])}}"
    }).make('line');
});
</script>
@endsection
