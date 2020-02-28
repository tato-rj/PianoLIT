    <div class="mb-4">
      <h6 class="bg-light rounded w-100 p-3 mb-4"><strong>App Logs ({{((new \App\Log\LogFactory)->count($user->id, 'app'))}})</strong></h6>
      <table class="table table-responsive table-hover w-100 border" id="log-app-table">
        <thead>
          <tr>
            <th class="border-0" scope="col">Date</th>
            <th class="border-0" scope="col">Api call</th>
            <th class="border-0" scope="col">Data</th>
          </tr>
        </thead>
        <tbody>
          @include('admin.pages.users.show.logs.app.rows', ['logs' => $user->log()->app, 'limit' => 5, 'more' => true])
        </tbody>
      </table>
    </div>