@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @chart([
      'url' => route('admin.stats.subscriptions'),
      'chart' => 'line',
      'type' => 'subscribers',
      'title' => 'Subscriptions',
      'subtitle' => 'Flow of subscribers over time',
      'height' => '50vh',
      'buttons' => [
        'type' => ['daily', 'monthly', 'yearly']
      ]
    ]) 

  </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript">
var quickchart = new QuickChart;

$(document).ready(function() {
    quickchart.setup({
      element: '#stats-subscribers', 
      url: "{{route('admin.stats.subscriptions', ['type' => 'daily'])}}"
    }).make('line');

    // quickchart.setup({
    //   element: '#stats-gender', 
    //   url: "{{route('admin.stats.users', ['type' => 'gender'])}}"
    // }).make('pie');

    // quickchart.setup({
    //   element: '#stats-confirmed', 
    //   url: "{{route('admin.stats.users', ['type' => 'confirmed'])}}"
    // }).make('pie');

    // quickchart.setup({
    //   element: '#stats-favorites', 
    //   url: "{{route('admin.stats.users', ['type' => 'favorites'])}}"
    // }).make('pie');
});
</script>
@endsection
