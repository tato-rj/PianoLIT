@include('components.datatable.actions', ['actions' => [
    'other' => [
      ['route' => route('admin.subscriptions.reports.show', $report->list_id), 'title' => 'More details', 'icon' => 'eye', 'target' => null]
    ]
]])
