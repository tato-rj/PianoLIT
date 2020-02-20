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
                        <div class="d-flex">
                            <div class="select-btn-group btn-group btn-group-sm mx-1">
                              <button data-model="users" data-type="daily" class="btn btn-secondary" selected>Daily</button>
                              <button data-model="users" data-type="monthly" class="btn btn-outline-secondary" style="border-left: 0; border-right: 0;">Monthly</button>
                              <button data-model="users" data-type="yearly" class="btn btn-outline-secondary">Yearly</button>
                            </div>
                            <div class="form-group-sm mx-1">
                                <select class="chart-select form-control" data-chart="line" data-parent="#stats-signups" name="origin">
                                    <option value="">Any origin</option>
                                    <option value="ios">iOS</option>
                                    <option value="web">Website</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div>
                        <canvas id="chart-signups" data-model="users" data-type="daily" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row"> 
        <div class="col-4 p-3">
            <div class="border py-4 px-3">
                <div id="stats-gender">
                    <div class="d-flex justify-content-between mb-4">
                        <h4 class="text-center"><strong>Gender</strong></h4>
                          <div class="form-group-sm mx-1">
                            <select class="chart-select form-control" data-chart="pie" data-parent="#stats-gender" name="origin">
                                <option value="">Any origin</option>
                                <option value="ios">iOS</option>
                                <option value="web">Website</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <canvas id="chart-gender" data-model="users" data-type="gender" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4 p-3">
            <div class="border py-4 px-3">
                <div id="stats-confirmed">
                    <div class="d-flex justify-content-between mb-4">
                        <h4 class="text-center"><strong>Email status</strong></h4>
                          <div class="form-group-sm mx-1">
                            <select class="chart-select form-control" data-chart="pie" data-parent="#stats-confirmed" name="origin">
                                <option value="">Any origin</option>
                                <option value="ios">iOS</option>
                                <option value="web">Website</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <canvas id="chart-confirmed" data-model="users" data-type="confirmed" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4 p-3">
            <div class="border py-4 px-3">
                <div id="stats-favorites">
                    <div class="d-flex justify-content-between mb-4">
                        <h4 class="text-center"><strong>Favorites</strong></h4>
                    </div>
                    <div>
                        <canvas id="chart-favorites" data-model="users" data-type="favorites" height="200"></canvas>
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
makeChart = function(type, container, route, params) {
    let $canvas = $(container).find('canvas');
    
    $canvas.addClass('opacity-4');


    $.get(route, params, function(data) {
        new Chart($canvas).destroy();
        nameToMethod(type, $canvas.attr('id'), data);
        $canvas.removeClass('opacity-4');
    });
}

createLineChart = function(element, data) {
    var chart = document.getElementById(element);
    var ctx = chart.getContext('2d');

    var myChart = new Chart(ctx, {
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

createPieChart = function(element, data) {
    var chart = document.getElementById(element);
    var ctx = chart.getContext('2d');

    var myChart = new Chart(ctx, {
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

nameToMethod = function(name, element, data) {
    let method = 'create' + ucfirst(name) + 'Chart';
    return window[method](element, data);
}

ucfirst = function(s) {
  if (typeof s !== 'string') return ''
  return s.charAt(0).toUpperCase() + s.slice(1)
}

convertHex = function(hex,opacity){
    hex = hex.replace('#','');
    r = parseInt(hex.substring(0,2), 16);
    g = parseInt(hex.substring(2,4), 16);
    b = parseInt(hex.substring(4,6), 16);

    result = 'rgba('+r+','+g+','+b+','+opacity/100+')';
    return result;
}
</script>



{{-- NEEDS UPDATE --}}
<script type="text/javascript">
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
