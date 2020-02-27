
    <div class="row mb-4">
        <div class="col-12">
          <div id="stats-logs" class="border py-4 px-3">
            <div class="mx-2 mb-4 d-flex d-apart">
              <div>
                <h4 class="mb-1"><strong>Activity logs</strong></h4>
              </div>
              <div>
                <select class="chart-select form-control form-control-sm" data-chart="line" data-parent="#stats-logs" name="logs_limit">
                  <option value="7">past 7 days</option>
                  <option value="14">past 14 days</option>
                  <option value="21">past 21 days</option>
                  <option value="28">past 28 days</option>
                </select>
              </div>
            </div>
            <div style="height: 40vh">
              <canvas id="chart-logs" class="w-100" height="300" data-model="users" data-type="logs"></canvas>
            </div>
          </div>
        </div>
    </div>

    @datatableRaw(['model' => 'users', 'rows' => 'admin.pages.stats.users.row', 'columns' => ['Date', 'Name', 'Visits', 'Favorites', 'Origin', 'Status', 'Last Active', '']])