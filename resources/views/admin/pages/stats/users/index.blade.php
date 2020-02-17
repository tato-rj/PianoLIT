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

    <div class="row"> 
        <div class="col-12 p-3">
            <div class="border py-4 px-3">
                <div id="stats-signups" class="carousel carousel-fade">
                    <div class="d-flex justify-content-between mb-4">
                        <h4 class="text-center"><strong>Flow of users over time</strong></h4>
                        <div class="select-btn-group btn-group btn-group-sm">
                          <button data-model="users" data-canvas="chart-signups" data-type="daily" class="btn btn-secondary">Daily</button>
                          <button data-model="users" data-canvas="chart-signups" data-type="monthly" class="btn btn-outline-secondary" style="border-left: 0; border-right: 0;">Monthly</button>
                          <button data-model="users" data-canvas="chart-signups" data-type="yearly" class="btn btn-outline-secondary">Yearly</button>
                        </div>
                    </div>
                    <div>
                        <canvas id="chart-signups" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{--     <div class="row"> 
      @include('admin.pages.stats.row', [
        'title' => 'Age groups',
        'subtitle' => 'Number of users per age group.',
        'id' => 'ageChart',
        'col' => '4',
        'data' => $usersAge])

      @include('admin.pages.stats.row', [
        'title' => 'Occupation',
        'subtitle' => 'Number of users by occupation.',
        'id' => 'occupationChart',
        'col' => '4',
        'data' => $usersOccupation])

      @include('admin.pages.stats.row', [
        'title' => 'Experience',
        'subtitle' => 'Number of users by experience.',
        'id' => 'experienceChart',
        'col' => '4',
        'data' => $usersExperience])
    </div> --}}

{{--     <div class="row">
        @include('admin.pages.stats.users.ranking')
    </div> --}}

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script type="text/javascript">
$(document).ready( function () {
    $('#users-table').DataTable({
        aaSorting: [],
        columnDefs: [{
                    targets: [3],
                    orderable: false
                }]
    });
} );
</script>
<script type="text/javascript">
$(document).ready(function() {
    getChartData('chart-signups', "{{route('admin.stats.users')}}", 'users', 'daily');
});

$('.select-btn-group .btn').on('click', function() {
    let $button = $(this);
    let canvas = $button.attr('data-canvas');
    $button.siblings().removeClass('btn-secondary').addClass('btn-outline-secondary');
    $button.removeClass('btn-outline-secondary').addClass('btn-secondary');

    getChartData(canvas, "{{route('admin.stats.users')}}", $button.attr('data-model'), $button.attr('data-type'));
});
</script>

<script type="text/javascript">
getChartData = function(canvas, route, model, type) {
    $('#'+canvas).addClass('opacity-4');
    $.get(route, {model: model, type: type}, function(data) {
        createLineChart(canvas, data);
        $('#'+canvas).removeClass('opacity-4');
    });
}
createLineChart = function(element, data) {
    var chart = document.getElementById(element);
    var ctx = chart.getContext('2d');
    // var activeRecords = JSON.parse(data);
    // var deletedRecords = JSON.parse(chart.getAttribute('data-deleted-records'));

    console.log(data);
    // var activeData = [];
    // // var deletedData = [];
    // var fields = [];

    // for (var i = 0; i < activeRecords.length; i++) {
    //     if (type == 'day') {
    //         fields.push(activeRecords[i].month+" "+activeRecords[i].day);
    //     } else if (type == 'month') {
    //         fields.push(activeRecords[i].month);
    //     } else {
    //         fields.push(activeRecords[i].year);
    //     }

    //     activeData.push(activeRecords[i].count);
    //     // deletedData.push(deletedRecords[i].count);
    // }

    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
            {
                label: 'New sign ups',
                data: data.records,
                pointBackgroundColor: 'rgba(52, 144, 220, 0.2)',
                pointBorderColor: 'rgba(52, 144, 220, 1)',
                backgroundColor: 'rgba(52, 144, 220, 0.2)',
                borderColor: 'rgba(52,144,220,1)',
                borderWidth: 1
            },
            // {
            //     label: 'Deleted accounts',
            //     data: deletedData,
            //     pointBackgroundColor: 'rgba(158, 158, 158, 0.2)',
            //     pointBorderColor: 'rgba(158, 158, 158, 1)',
            //     backgroundColor: 'rgba(158, 158, 158, 0.2)',
            //     borderColor: 'rgba(158, 158, 158, 1)',
            //     borderWidth: 1
            // }
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
</script>



{{-- NEEDS UPDATE --}}
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
let ageRecords = JSON.parse($('#ageChart').attr('data-records'));
let ages = [];
let age_users_count = [];

for (var i=0; i < ageRecords.length; i++) {
  ages.push(ageRecords[i].age);
  age_users_count.push(ageRecords[i].count);
}

var ageChartElement = document.getElementById("ageChart").getContext('2d');
var ageChart = new Chart(ageChartElement,{
    type: 'pie',
    data: {
        labels: ages,
        datasets: [{
            data: age_users_count,
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

let occupationRecords = JSON.parse($('#occupationChart').attr('data-records'));
let occupations = [];
let occupation_users_count = [];

for (var i=0; i < occupationRecords.length; i++) {
  occupations.push(occupationRecords[i].occupation);
  occupation_users_count.push(occupationRecords[i].count);
}

var occupationChartElement = document.getElementById("occupationChart").getContext('2d');
var occupationChart = new Chart(occupationChartElement,{
    type: 'pie',
    data: {
        labels: occupations,
        datasets: [{
            data: occupation_users_count,
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

let experienceRecords = JSON.parse($('#experienceChart').attr('data-records'));
let experiences = [];
let experience_users_count = [];

for (var i=0; i < experienceRecords.length; i++) {
  experiences.push(experienceRecords[i].experience);
  experience_users_count.push(experienceRecords[i].count);
}

var experienceChartElement = document.getElementById("experienceChart").getContext('2d');
var experienceChart = new Chart(experienceChartElement,{
    type: 'pie',
    data: {
        labels: experiences,
        datasets: [{
            data: experience_users_count,
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
@endsection
