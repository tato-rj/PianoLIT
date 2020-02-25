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
          @include('admin.pages.users.show.logs.web.rows', ['logs' => $user->log()->web, 'limit' => 5, 'more' => true])
        </tbody>
      </table>
    </div>