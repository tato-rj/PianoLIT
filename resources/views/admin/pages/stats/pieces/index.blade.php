@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
  @include('admin.components.breadcrumb', [
    'title' => 'Pieces statistics',
    'description' => 'Charts and graphs on the pieces and their level, period and tags'])

    <div class="row"> 
      @include('admin.pages.stats.row', [
        'title' => 'Tags',
        'subtitle' => 'How the ' . $tagsCount . ' tags are distributed in the database.',
        'id' => 'tagsChart',
        'col' => '12',
        'data' => $tagStats])
    </div>

    <div class="row"> 
      @include('admin.pages.stats.row', [
        'title' => 'Composers',
        'subtitle' => 'Number of pieces each of the ' . $composersCount . ' composers have in the database.',
        'id' => 'composersChart',
        'col' => '12',
        'data' => $composersStats])
    </div>

    <div class="row"> 
      @include('admin.pages.stats.row', [
        'title' => 'Periods',
        'subtitle' => 'Number of pieces in each period.',
        'id' => 'periodsChart',
        'col' => '4',
        'data' => $periodsStats])

      @include('admin.pages.stats.row', [
        'title' => 'Levels',
        'subtitle' => 'Number of pieces per level.',
        'id' => 'levelsChart',
        'col' => '4',
        'data' => $levelsStats])

      @include('admin.pages.stats.row', [
        'title' => 'Recordings count',
        'subtitle' => 'Pieces by number of recordings.',
        'id' => 'recChart',
        'col' => '4',
        'data' => $recStats])
    </div>

    <div class="row my-3">
        @include('admin.pages.stats.pieces.ranking')
    </div>

  </div>
</div>

@include('admin.components.modals.results', ['title' => 'We found the following pieces'])

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).ready( function () {
    $('#pieces-table').DataTable({
    'order': [[1, 'desc']],
    });
} );
</script>

<script type="text/javascript">
var colors = ['#5eb58a', '#f5c86d', '#f3686f', '#9a40d5', '#e3342f', '#f6993f', '#38c172', '#4dc0b5', '#3490dc', '#6574cd', '#9561e2', '#f66d9b'];
function getRandom(arr, n = 1) {
    var requests = n;
    var result = new Array(n),
        len = arr.length,
        taken = new Array(len);
    if (n > len)
        throw new RangeError("getRandom: more elements taken than available");
    while (n--) {
        var x = Math.floor(Math.random() * len);
        result[n] = arr[x in taken ? taken[x] : x];
        taken[x] = --len in taken ? taken[len] : len;
    }

    return requests == 1 ? result[0] : result;
}
function getElements(arr, n) {
    return arr.slice(0, n);
}
</script>
<script type="text/javascript">

let tagsRecords = JSON.parse($('#tagsChart').attr('data-records'));
let tags = [];
let tags_pieces_count = [];
let tags_count = tagsRecords.length;

for (var i=0; i < tags_count; i++) {
  tags.push(tagsRecords[i].name);
  tags_pieces_count.push(tagsRecords[i].pieces_count);
}
var tagsChartElement = document.getElementById("tagsChart").getContext('2d');
var tagsChart = new Chart(tagsChartElement, {
    type: 'bar',
    data: {
        labels: tags,
        datasets: [{
            data: tags_pieces_count,
            backgroundColor: getRandom(colors)
        }]
    },
    options: {
        legend: {
          display: false
        },
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    stepSize: 2
                }
            }],
            xAxes: [{
                ticks: {
                  autoSkip: false
                }
            }]
        },
        events: ["mousemove", "mouseout", "click"],
        onClick: function(element, item) {
            let tag = item[0]._view.label;
            let $modal = $('#results-modal');
            $modal.find('.modal-body').html('<p class="text-center text-muted my-4"><i>loading...</i></p>');
            $modal.modal('show');
          $.get("/piano-lit/tags/"+tag+"/pieces", function(data, status){
            $modal.find('.modal-body').html(data);
          });
        }
    }
});
</script>

<script type="text/javascript">
let composersRecords = JSON.parse($('#composersChart').attr('data-records'));
let composers = [];
let composers_pieces_count = [];
let composers_count = composersRecords.length;

for (var i=0; i < composers_count; i++) {
  composers.push(composersRecords[i].short_name);
  composers_pieces_count.push(composersRecords[i].pieces_count);
}

var composersChartElement = document.getElementById("composersChart").getContext('2d');
var composersChart = new Chart(composersChartElement, {
    type: 'bar',
    data: {
        labels: composers,
        datasets: [{
            data: composers_pieces_count,
            backgroundColor: getRandom(colors)
        }]
    },
    options: {
        legend: {
          display: false
        },
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    stepSize: 2
                }
            }],
            xAxes: [{
                ticks: {
                  autoSkip: false
                }
            }]
        },
        layout: {
            padding: {
                left: 0,
                right: 0,
                top: 0,
                bottom: 0
            }
        },
        events: ["mousemove", "mouseout", "click"],
        onClick: function(element, item) {
            let label = item[0]._view.label;
            let cut = label.lastIndexOf('.') + 1;
            let composer = label.substring(cut, label.length);
            console.log(composer);
            let $modal = $('#results-modal');
            $modal.find('.modal-body').html('<p class="text-center text-muted my-4"><i>loading...</i></p>');
            $modal.modal('show');
          $.get("/piano-lit/composers/"+composer+"/pieces", function(data, status){
            $modal.find('.modal-body').html(data);
          });
        }
    }
});
</script>
<script type="text/javascript">
let periodsRecords = JSON.parse($('#periodsChart').attr('data-records'));
let periods = [];
let periods_pieces_count = [];

for (var i=0; i < periodsRecords.length; i++) {
  periods.push(periodsRecords[i].name);
  periods_pieces_count.push(periodsRecords[i].pieces_count);
}

var periodsChartElement = document.getElementById("periodsChart").getContext('2d');
var periodsChart = new Chart(periodsChartElement,{
    type: 'pie',
    data: {
        labels: periods,
        datasets: [{
            data: periods_pieces_count,
            backgroundColor: getRandom(colors, 6)
        }]
    },
    options: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 20
          }
        },
        events: ["mousemove", "mouseout", "click"],
        onClick: function(element, item) {
            let period = item[0]._view.label;
            let $modal = $('#results-modal');
            $modal.find('.modal-body').html('<p class="text-center text-muted my-4"><i>loading...</i></p>');
            $modal.modal('show');
          $.get("/piano-lit/tags/"+period+"/pieces", function(data, status){
            
            $modal.find('.modal-body').html(data);
          });
        }
    }
});
</script>
<script type="text/javascript">
let levelsRecords = JSON.parse($('#levelsChart').attr('data-records'));
let levels = [];
let levels_pieces_count = [];

for (var i=0; i < levelsRecords.length; i++) {
  levels.push(levelsRecords[i].name);
  levels_pieces_count.push(levelsRecords[i].pieces_count);
}

var levelsChartElement = document.getElementById("levelsChart").getContext('2d');
var levelsChart = new Chart(levelsChartElement,{
    type: 'pie',
    data: {
        labels: levels,
        datasets: [{
            data: levels_pieces_count,
            backgroundColor: getElements(colors, 4)
        }]
    },
    options: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 20
          }
        },
        events: ["mousemove", "mouseout", "click"],
        onClick: function(element, item) {
            let level = item[0]._view.label;
            let $modal = $('#results-modal');
            $modal.find('.modal-body').html('<p class="text-center text-muted my-4"><i>loading...</i></p>');
            $modal.modal('show');
          $.get("/piano-lit/tags/"+level+"/pieces", function(data, status){
            
            $modal.find('.modal-body').html(data);
          });
        }
    }
});
</script>
<script type="text/javascript">
let recRecords = JSON.parse($('#recChart').attr('data-records'));
let rec_pieces_count = [0,0,0,0];

for (var i=0; i < Object.keys(recRecords).length; i++) {
    let index = Object.keys(recRecords)[i];
    rec_pieces_count[index] = recRecords[index].count;
}

var recChartElement = document.getElementById("recChart").getContext('2d');
var recChart = new Chart(recChartElement,{
    type: 'pie',
    data: {
        labels: ['0 recorgings', '1 recording','2 recordings','3 recordings'],
        datasets: [{
            data: rec_pieces_count,
            backgroundColor: getRandom(colors, 4)
        }]
    },
    options: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            padding: 20
          }
        },
        events: ["mousemove", "mouseout", "click"],
        // onClick: function(element, item) {
        //     let level = item[0]._view.label;
        //     let $modal = $('#results-modal');
        //     $modal.find('.modal-body').html('<p class="text-center text-muted my-4"><i>loading...</i></p>');
        //     $modal.modal('show');
        //   $.get("/piano-lit/tags/"+level+"/pieces", function(data, status){
            
        //     $modal.find('.modal-body').html(data);
        //   });
        // }
    }
});
</script>
@endsection
