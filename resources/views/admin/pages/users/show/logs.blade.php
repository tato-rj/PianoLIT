@include('admin.pages.users.show.title', ['title' => 'Activity Logs', 'icon' => 'clipboard-check'])

<div class="row">
  <div class="col-12">
  	@table([
  		'id' => 'log-app-table',
  		'title' => 'App Logs (' . ((new \App\Log\LogFactory)->count($user->id, 'app')) . ')',
  		'headers' => ['Date', 'Api call', 'Data'],
      'more' => route('admin.users.load-logs', ['user' => $user->id, 'type' => 'app']),
  		'rows' => view('admin.pages.users.show.logs.app-rows', [
        'user' => $user, 
        'logs' => collect($user->log()->app)->take(5),
      ])
  	])
    @table([
      'id' => 'log-webapp-table',
      'title' => 'Webapp Logs (' . ((new \App\Log\LogFactory)->count($user->id, 'webapp')) . ')',
      'headers' => ['Date', 'Api call', 'Data'],
      'more' => route('admin.users.load-logs', ['user' => $user->id, 'type' => 'webapp']),
      'rows' => view('admin.pages.users.show.logs.web-rows', [
        'user' => $user, 
        'logs' => collect($user->log()->webapp)->take(5)
      ])
    ])
  	@table([
  		'id' => 'log-web-table',
  		'title' => 'Web Logs (' . ((new \App\Log\LogFactory)->count($user->id, 'web')) . ')',
  		'headers' => ['Date', 'Api call', 'Data'],
      'more' => route('admin.users.load-logs', ['user' => $user->id, 'type' => 'web']),
  		'rows' => view('admin.pages.users.show.logs.web-rows', [
        'user' => $user, 
        'logs' => collect($user->log()->web)->take(5)
      ])
  	])
  </div>
</div>

@component('components.modal', ['title' => 'Log data', 'size' => 'lg', 'id' => 'modal-log-data'])
@slot('body')
<div id="data-container"></div>
@endslot
@endcomponent