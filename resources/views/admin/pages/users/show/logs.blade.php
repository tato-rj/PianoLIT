<div class="tab-pane fade {{request('section') == 'logs' ? 'show active' : null}} m-3" id="logs">
  <div class="mb-4">
    <h6 class="bg-light rounded w-100 p-3 mb-4"><strong>App Logs</strong></h6>
    <table class="table table-hover w-100 border" id="log-app-table">
      <thead>
        <tr>
          <th class="border-0" scope="col">Date</th>
          <th class="border-0" scope="col">Api call</th>
          <th class="border-0" scope="col">Data</th>
        </tr>
      </thead>
      <tbody>
        @foreach($user->log()->app as $date => $event)
        <tr>
          <td class="text-nowrap">{{carbon($date)->format('M j\\, Y \\a\\t h:i A')}}</td>

          <td class="dataTables_main_column">{{$event->url}}</td>

          <td>
            @if(count($event->data) > 1)
            {{slug_str($event->data[0])}} - <strong>{{$event->data[1]}}</strong>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div>
    <h6 class="bg-light rounded w-100 p-3 mb-4"><strong>Web Logs</strong></h6>
    <table class="table table-hover border w-100" id="log-web-table">
      <thead>
        <tr>
          <th class="border-0" scope="col">Date</th>
          <th class="border-0" scope="col">URL</th>
          <th class="border-0" scope="col">Data</th>
        </tr>
      </thead>
      <tbody>
        @foreach($user->log()->web as $date => $event)
        <tr>
          <td class="text-nowrap">{{carbon($date)->format('M j\\, Y \\a\\t h:i A')}}</td>

          <td class="dataTables_main_column">{{$event->url}}</td>

          <td>{{json_encode($event->data)}}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>