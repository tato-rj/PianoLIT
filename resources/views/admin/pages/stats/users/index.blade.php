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
(new DataTableRaw({table: '#users-table', options: {order: [[5, 'desc']]}})).create();
</script>
<script type="text/javascript">
// let graph = document.getElementById("logs-chart").getContext('2d');
// let graphData = JSON.parse($('#logs-chart').attr('data-records'));
// let labels = [];
// let app = [];
// let web = [];

// for (var i=0; i < graphData.length; i++) {
//   labels.push(graphData[i].day);
//   app.push(graphData[i].app);
//   web.push(graphData[i].web);
// }

// let piecesGraph = new Chart(graph, {
//     type: 'line',
//     data: {
//       labels: labels,
//       datasets: [
//         {
//           label: 'App logs',
//           backgroundColor: '#5eb58a',
//           borderColor: '#5eb58a',
//           data: app,
//           fill: false,
//         },
//         {
//           label: 'Web logs',
//           backgroundColor: '#f5c86d',
//           borderColor: '#f5c86d',
//           data: web,
//           fill: false,
//         }
//       ]
//     },

//     options: {
//       scales: {
//           yAxes: [{
//               ticks: {
//                   beginAtZero: true,
//                   stepSize: stepSize()
//               }
//           }],
//           xAxes: [{
//               // ticks: {
//               //   autoSkip: false
//               // }
//           }]
//       }
//     }
// });

// function stepSize()
// {
//   return Math.ceil(Math.max(...[Math.max(...app), Math.max(...web)])/5);
// }
</script>

<script type="text/javascript">
var quickchart = new QuickChart;

$(document).ready(function() {
    quickchart.setup({
      element: '#stats-logs', 
      url: "{{route('admin.stats.users', ['type' => 'logs'])}}"
    }).make('line');

    quickchart.setup({
      element: '#stats-signups', 
      url: "{{route('admin.stats.users', ['type' => 'daily'])}}"
    }).make('line');

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
});

$('.select-btn-group .btn').on('click', function() {
    let $button = $(this);
    let $option = getSelectedOptionFrom('#stats-signups');
    let $canvas = $('#stats-signups').find('canvas');

    $button.siblings().removeClass('btn-secondary').addClass('btn-outline-secondary').toggleAttr('selected');
    $button.removeClass('btn-outline-secondary').addClass('btn-secondary').toggleAttr('selected');

    $canvas.attr('data-type', $button.attr('data-type'));

    quickchart.setup({
      element: '#stats-signups', 
      url: "{{route('admin.stats.users')}}?type="+$canvas.attr('data-type')+"&origin="+$option.val()
    }).make('line');
});

$('.chart-select').on('change', function() {
    let $option = $(this);
    let container = $option.attr('data-parent');
    let chart = $option.attr('data-chart');
    let type = $(container).find('canvas').attr('data-type');
    let origin = $option.val();

    quickchart.setup({
      element: container, 
      url: "{{route('admin.stats.users')}}?type="+type+"&origin="+origin
    }).make(chart);
});

function getSelectedOptionFrom(elem) {
    return $(elem).find('.chart-select option:selected');
}
</script>
@endsection
