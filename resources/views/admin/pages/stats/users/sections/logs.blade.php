<div class="row mb-4">
  <div class="col-12">
    @chart([
      'url' => route('admin.stats.users'),
      'chart' => 'line',
      'type' => 'logs',
      'title' => 'Activity logs',
      'subtitle' => 'Number of logs per day',
      'select' => [
        'logs_limit' => [
          ['label' => 'past 7 days', 'value' => 7],
          ['label' => 'past 14 days', 'value' => 14],
          ['label' => 'past 21 days', 'value' => 21],
          ['label' => 'past 28 days', 'value' => 28]
        ]
      ]
    ])
  </div>
</div>

@datatableRaw(['model' => 'users', 'rows' => 'admin.pages.stats.users.row', 'columns' => ['Date', 'Name', 'Visits', 'Favorites', 'Origin', 'Status', 'Last Active', '']])