
    <div class="row mb-4">
        <div class="col-12">
          <div class="border py-4 px-3">
            <div class="mx-2 mb-4 d-flex d-apart">
              <div>
                <h4 class="mb-1"><strong>Activity logs</strong></h4>
                <p class="text-muted">Activity over the last 7 days</p>
              </div>
              <div>
                <form action="{{url()->current()}}" method="GET">
                  <div class="form-group">
                    <select name="logs_limit" class="form-control form-control-sm" onchange="this.form.submit()">
                      <option value="7" {{request('logs_limit') == 7 ? 'selected' : null}}>past 7 days</option>
                      <option value="14" {{request('logs_limit') == 14 ? 'selected' : null}}>past 14 days</option>
                      <option value="21" {{request('logs_limit') == 21 ? 'selected' : null}}>past 21 days</option>
                      <option value="28" {{request('logs_limit') == 28 ? 'selected' : null}}>past 28 days</option>
                    </select>
                  </div>
                </form>
              </div>
            </div>
            <canvas id="logs-chart" class="w-100" height="300" data-records="{{json_encode($latest_logs)}}"></canvas>
          </div>
        </div>
    </div>

    @datatableRaw(['model' => 'users', 'columns' => ['checkbox', 'Date', 'Name', 'Visits', 'Origin', 'Status', 'Last Active', 'Super User', '']])