@extends('admin.layouts.app')

@section('head')
@endsection

@section('content')

<div class="content-wrapper">
  <div class="container-fluid">
    @include('admin.components.page.title', ['title' => 'Subscriber analytics', 'subtitle' => 'Follow the growth of the PianoLIT email community.'])

    @chart([
      'url' => route('admin.analytics.subscriptions'),
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
      element: '#analytics-subscribers',
      url: "{{route('admin.analytics.subscriptions', ['type' => 'daily'])}}"
    }).make('line');

    // quickchart.setup({
    //   element: '#analytics-gender',
    //   url: "{{route('admin.analytics.users', ['type' => 'gender'])}}"
    // }).make('pie');

    // quickchart.setup({
    //   element: '#analytics-confirmed',
    //   url: "{{route('admin.analytics.users', ['type' => 'confirmed'])}}"
    // }).make('pie');

    // quickchart.setup({
    //   element: '#analytics-favorites',
    //   url: "{{route('admin.analytics.users', ['type' => 'favorites'])}}"
    // }).make('pie');
});
</script>
@endsection
