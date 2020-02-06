@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Users',
    'description' => 'View detailed information about the users'])

    <div class="row mb-4">
      <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
          <form method="GET" action="{{route('admin.memberships.validate.all')}}">
            @csrf
            <button class="btn btn-sm btn-success"><i class="fas fa-clipboard-check mr-2"></i>Validate all subscriptions</button>
          </form>
        </div>
        <div>
          @include('admin.components.filters.users', ['filters' => []])
        </div>
      </div>
      <div class="col-12 mt-2" id="multi-select" style="display: none;">
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
          <div><strong><span id="selected-count">3</span> selected</strong></div>
          <div>
            <form method="POST" action="{{route('admin.users.destroy-many')}}">
              @csrf
              @method('DELETE')
              <input type="hidden" name="ids">
              <button type="submit" class="btn btn-sm btn-warning">Delete selected</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12">
          <div class="border py-4 px-3">
            <div class="ml-2 mb-4">
              <h4 class="mb-1"><strong>Activity logs</strong></h4>
              <p class="text-muted">Activity over the last 7 days</p>
            </div>
            <canvas id="logs-chart" class="w-100" height="300" data-records="{{json_encode($latest_logs)}}"></canvas>
          </div>
        </div>
    </div>

    @datatableRaw(['model' => 'users', 'columns' => ['checkbox', 'Date', 'Name', 'Visits', 'Origin', 'Status', 'Last Active', 'Super User', '']])

  </div>
</div>

@include('admin.components.modals.delete')
@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
(new DataTableRaw({table: '#users-table', dontSortFirst: true, options: {order: [[6, 'desc']]}})).create();
</script>
<script type="text/javascript">
$('.check-user').on('change', function() {
  let $selected = $('.check-user:checked');
  let $container = $('#multi-select');
  let ids = [];

  $selected.each(function() {
    ids.push($(this).attr('data-id'));
  });

  $('input[name="ids"]').val(JSON.stringify(ids));

  if ($selected.length > 0) {
    $container.find('#selected-count').text($selected.length);
    $container.show();
  } else {
    $container.hide();
  }
});
</script>

<script type="text/javascript">
let graph = document.getElementById("logs-chart").getContext('2d');
let graphData = JSON.parse($('#logs-chart').attr('data-records'));
let labels = [];
let app = [];
let web = [];

for (var i=0; i < graphData.length; i++) {
  labels.push(graphData[i].day);
  app.push(graphData[i].app);
  web.push(graphData[i].web);
}

let piecesGraph = new Chart(graph, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'App logs',
          backgroundColor: '#5eb58a',
          borderColor: '#5eb58a',
          data: app,
          fill: false,
        },
        {
          label: 'Web logs',
          backgroundColor: '#f5c86d',
          borderColor: '#f5c86d',
          data: web,
          fill: false,
        }
      ]
    },

    options: {
      scales: {
          yAxes: [{
              ticks: {
                  beginAtZero: true,
                  stepSize: stepSize()
              }
          }],
          xAxes: [{
              // ticks: {
              //   autoSkip: false
              // }
          }]
      }
    }
});

function stepSize()
{
  return Math.ceil(Math.max(...[Math.max(...app), Math.max(...web)])/5);
}
</script>
@endsection