@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/plug-ins/1.10.19/integration/font-awesome/dataTables.fontAwesome.css">

<script>
    window.pieces = <?php echo json_encode([
        'count' => $pieces->count()
    ]); ?>
</script>
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
        'title' => 'Pieces by tags',
        'subtitle' => 'Ranking of the number of tags used in the pieces.',
        'id' => 'tagsPiecesChart',
        'col' => '12',
        'data' => $tagsPiecesStats])
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
        'title' => 'Copyright',
        'subtitle' => 'Number of pieces by copyright.',
        'id' => 'copyrightChart',
        'col' => '4',
        'data' => $publicDomainCount])
    </div>

    <div class="row">
      @include('admin.pages.stats.row', [
        'title' => 'Gender',
        'subtitle' => 'Male vs female composers.',
        'id' => 'genderChart',
        'col' => '4',
        'data' => $genderStats])

      @include('admin.pages.stats.row', [
        'title' => 'Videos',
        'subtitle' => 'Pieces by videos.',
        'id' => 'videosChart',
        'col' => '4',
        'data' => $videosCount])

      @include('admin.pages.stats.row', [
        'title' => 'iTunes recordings',
        'subtitle' => 'Pieces by itunes recordings.',
        'id' => 'itunesChart',
        'col' => '4',
        'data' => $itunesCount])
    </div>

    <div class="row my-3">
        @include('admin.pages.stats.pieces.ranking')
    </div>

  </div>
</div>

@component('admin.components.modals.results', ['title' => 'We found the following pieces'])
@endcomponent

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
                    stepSize: getStepSize(tags_pieces_count)
                }
            }],
            xAxes: [{
                ticks: {
                  autoSkip: false
                }
            }]
        }
    }
});
</script>

<script type="text/javascript">

let tagsPiecesRecords = JSON.parse($('#tagsPiecesChart').attr('data-records'));
let tags_counts = [];
let records_count = [];

for (var property in tagsPiecesRecords) {
  tags_counts.push(property + ' tags');
  records_count.push(tagsPiecesRecords[property].length);
}

var tagsPiecesChartElement = document.getElementById("tagsPiecesChart").getContext('2d');

var tagsPiecesChart = new Chart(tagsPiecesChartElement, {
    type: 'bar',
    data: {
        labels: tags_counts,
        datasets: [{
            data: records_count,
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
                    stepSize: getStepSize(records_count)
                }
            }],
            xAxes: [{
                ticks: {
                  autoSkip: false
                }
            }]
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
        }
    }
});
</script>

<script type="text/javascript">
let genderRecords = JSON.parse($('#genderChart').attr('data-records'));
let gender_pieces_count = [];

for (var i=0; i < Object.keys(genderRecords).length; i++) {
    let index = Object.keys(genderRecords)[i];
    gender_pieces_count[i] = genderRecords[index].count;
}
console.log(gender_pieces_count);
var genderChartElement = document.getElementById("genderChart").getContext('2d');
var genderChart = new Chart(genderChartElement,{
    type: 'pie',
    data: {
        labels: ['Female', 'Male'],
        datasets: [{
            data: gender_pieces_count,
            backgroundColor: ['#f66d9b', '#3490dc']
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
    }
});
</script>

<script type="text/javascript">
let videosCount = JSON.parse($('#videosChart').attr('data-records'));

var videosChartElement = document.getElementById("videosChart").getContext('2d');
var videosChart = new Chart(videosChartElement,{
    type: 'pie',
    data: {
        labels: ['Has videos', 'Missing videos'],
        datasets: [{
            data: [videosCount, window.pieces.count - videosCount],
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
    }
});
</script>

<script type="text/javascript">
let itunesCount = JSON.parse($('#itunesChart').attr('data-records'));

var itunesChartElement = document.getElementById("itunesChart").getContext('2d');
var itunesChart = new Chart(itunesChartElement,{
    type: 'pie',
    data: {
        labels: ['Has itunes recordings', 'Missing itunes recordings'],
        datasets: [{
            data: [itunesCount, window.pieces.count - itunesCount],
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
    }
});
</script>

<script type="text/javascript">
let publicDomainCount = JSON.parse($('#copyrightChart').attr('data-records'));

var copyrightChartElement = document.getElementById("copyrightChart").getContext('2d');
var copyrightChart = new Chart(copyrightChartElement,{
    type: 'pie',
    data: {
        labels: ['Pieces in public domain', 'Pieces not in public domain'],
        datasets: [{
            data: [publicDomainCount, window.pieces.count - publicDomainCount],
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
    }
});
</script>
@endsection
