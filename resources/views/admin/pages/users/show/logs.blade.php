@include('admin.pages.users.show.title', ['title' => 'Activity Logs', 'icon' => 'clipboard-check'])

<div class="row">
  <div class="col-12 mb-4">
  	@table([
  		'id' => 'log-app-table',
  		'title' => 'App Logs (' . ((new \App\Log\LogFactory)->count($user->id, 'app')) . ')',
  		'headers' => ['Date', 'Api call', 'Data'],
  		'rows' => view('admin.pages.users.show.logs.app-rows', ['user' => $user, 'logs' => $user->log()->app, 'limit' => 5, 'more' => true])
  	])
  	@table([
  		'id' => 'log-web-table',
  		'title' => 'Web Logs (' . ((new \App\Log\LogFactory)->count($user->id, 'web')) . ')',
  		'headers' => ['Date', 'Api call', 'Data'],
  		'rows' => view('admin.pages.users.show.logs.web-rows', ['user' => $user, 'logs' => $user->log()->web, 'limit' => 5, 'more' => true])
  	])
  </div>
</div>

@modal(['title' => 'Log data', 'size' => 'lg'])
<div id="data-container"></div>
@endmodal