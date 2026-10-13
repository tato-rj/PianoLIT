<div class="row">
  <div class="col-12">
  	@table([
  		'id' => 'log-app-table',
        'title' => 'App Logs (' . ($activityCounts['app'] ?? 0) . ')',
  		'headers' => ['Date', 'Api call', 'Data'],
      'more' => route('admin.users.load-logs', ['user' => $user->id, 'type' => 'app']),
  		'rows' => view('admin.pages.users.show.logs.app-rows', [
        'user' => $user, 
        'logs' => $appLogs,
      ])
  	])
    <div class="card mb-4"><div class="card-body">
      <h4>Website visit timelines</h4>
      <p>Read pages, navigation, filters, favorites and media playback in chronological order.</p>
      <a class="btn btn-primary" href="{{ route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits']) }}">Open visit history</a>
    </div></div>
  </div>
</div>

@component('components.modal', ['title' => 'Log data', 'size' => 'lg', 'id' => 'modal-log-data'])
@slot('body')
<div id="data-container"></div>
@endslot
@endcomponent
