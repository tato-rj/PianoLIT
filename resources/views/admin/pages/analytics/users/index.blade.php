@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    <div class="row"> 
        <div class="col-12 mb-4">
          @chart([
            'url' => route('admin.analytics.users'),
            'chart' => 'line',
            'type' => 'signups',
            'title' => 'Sign ups',
            'subtitle' => 'Flow of users over time',
            'height' => '50vh',
            'select' => [
              'origin' => [
                ['label' => 'Any origin', 'value' => null],
                ['label' => 'iOS', 'value' => 'ios'],
                ['label' => 'Website', 'value' => 'web']
              ]
            ],
            'buttons' => [
              'type' => ['daily', 'monthly', 'yearly']
            ]
          ])
        </div>
    </div>
    <div class="row"> 
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.analytics.users'),
            'chart' => 'pie',
            'type' => 'gender',
            'title' => 'Gender',
            'subtitle' => 'Users by gender',
            'height' => '30vh',
            'select' => [
              'origin' => [
                ['label' => 'Any origin', 'value' => null],
                ['label' => 'iOS', 'value' => 'ios'],
                ['label' => 'Website', 'value' => 'web']
              ]
            ]
          ])
        </div>
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.analytics.users'),
            'chart' => 'pie',
            'type' => 'confirmed',
            'title' => 'Email status',
            'subtitle' => 'Users with confirmed emails',
            'height' => '30vh',
            'select' => [
              'origin' => [
                ['label' => 'Any origin', 'value' => null],
                ['label' => 'iOS', 'value' => 'ios'],
                ['label' => 'Website', 'value' => 'web']
              ]
            ]
          ])
        </div>
        <div class="col-lg-4 col-md-4 col-12 mb-4">
          @chart([
            'url' => route('admin.analytics.users'),
            'chart' => 'pie',
            'type' => 'favorites',
            'title' => 'Favorites',
            'subtitle' => 'Users with favorited pieces',
            'height' => '30vh',
          ])
        </div>
    </div>

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
var quickchart = new QuickChart;

$(document).ready(function() {
    quickchart.setup({
      element: '#analytics-signups',
      url: "{{route('admin.analytics.users', ['type' => 'daily'])}}"
    }).make('line');

    quickchart.setup({
      element: '#analytics-gender',
      url: "{{route('admin.analytics.users', ['type' => 'gender'])}}"
    }).make('pie');

    quickchart.setup({
      element: '#analytics-confirmed',
      url: "{{route('admin.analytics.users', ['type' => 'confirmed'])}}"
    }).make('pie');

    quickchart.setup({
      element: '#analytics-favorites',
      url: "{{route('admin.analytics.users', ['type' => 'favorites'])}}"
    }).make('pie');
});
</script>
@endsection
