@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Users statistics',
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
(new DataTableRaw({table: '#users-table'})).create();
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
<script type="text/javascript">
$(document).ready(function() {
    makeChart('line', '#stats-signups', "{{route('admin.stats.users')}}", {
        model: 'users', 
        type: 'daily'
    });

    makeChart('pie', '#stats-gender', "{{route('admin.stats.users')}}", {
        model: 'users', 
        type: 'gender'
    });

    makeChart('pie', '#stats-confirmed', "{{route('admin.stats.users')}}", {
        model: 'users', 
        type: 'confirmed'
    });

    makeChart('pie', '#stats-favorites', "{{route('admin.stats.users')}}", {
        model: 'users', 
        type: 'favorites'
    });
});

$('.select-btn-group .btn').on('click', function() {
    let $button = $(this);
    let $option = getSelectedOptionFrom('#stats-signups');
    let $canvas = $('#stats-signups').find('canvas');

    $button.siblings().removeClass('btn-secondary').addClass('btn-outline-secondary').toggleAttr('selected');
    $button.removeClass('btn-outline-secondary').addClass('btn-secondary').toggleAttr('selected');

    $canvas.attr({
        'data-model': $button.attr('data-model'), 
        'data-type': $button.attr('data-type')
    });

    makeChart('line', '#stats-signups', "{{route('admin.stats.users')}}", {
        model: $canvas.attr('data-model'), 
        type: $canvas.attr('data-type'),
        origin: $option.val()
    });
});

$('.chart-select').on('change', function() {
    let $option = $(this);
    let type = $option.attr('data-chart');
    let container = $option.attr('data-parent');

    makeChart(type, container, "{{route('admin.stats.users')}}", {
        model: $(container).find('canvas').attr('data-model'), 
        type: $(container).find('canvas').attr('data-type'),
        origin: $option.val()
    });
});

function getSelectedOptionFrom(elem) {
    return $(elem).find('.chart-select option:selected');
}
</script>

<script type="text/javascript">
var charts = [];

makeChart = function(type, container, route, params) {
    let $canvas = $(container).find('canvas');
    
    $canvas.addClass('opacity-4');

    $.get(route, params, function(data) {
        destroy($canvas);
        draw(type, $canvas, data);
        $canvas.removeClass('opacity-4');
    });
}

line = function(canvas, data) {
    return new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
            {
                label: data.title,
                data: data.records,
                pointBackgroundColor: convertHex(data.colors[0], 5),
                pointBorderColor: data.colors[0],
                backgroundColor: convertHex(data.colors[0], 5),
                borderColor: data.colors[0],
                borderWidth: 1
            },
        ]},
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        min: 0,
                        beginAtZero: true,
                        callback: function(value, index, values) {
                            if (Math.floor(value) === value) {
                                return value;
                            }
                        }
                    }
                }]
            }
        }
    }); 
}

pie = function(canvas, data) {
    return new Chart(canvas, {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.records,
                backgroundColor: data.colors
            }]
        },
        options: {
            legend: {
              display: true,
              position: 'bottom',
              labels: {
                padding: 20
              }
            }
        }
    }); 
}

draw = function(method, canvas, data) {
    let chart = window[method](canvas, data);
    let id = canvas.attr('id');
    let $label = $('span[data-origin="'+id+'"]');

    charts[id] = chart;
    $label.text(data.records.reduce(arraySum));
    $label.parent().show();
}

destroy = function(canvas) {
    let chart = charts[canvas.attr('id')];

    if (chart)
        chart.destroy();
}

convertHex = function(hex,opacity){
    hex = hex.replace('#','');
    r = parseInt(hex.substring(0,2), 16);
    g = parseInt(hex.substring(2,4), 16);
    b = parseInt(hex.substring(4,6), 16);

    result = 'rgba('+r+','+g+','+b+','+opacity/100+')';
    return result;
}

arraySum = function (total, num) {
  return total + num;
}
</script>
@endsection
