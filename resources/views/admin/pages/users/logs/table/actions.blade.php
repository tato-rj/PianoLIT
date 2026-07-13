@include('components.datatable.actions', ['actions' => [
    'other' => [
      ['route' => route('admin.users.show', $item), 'title' => 'More details', 'icon' => 'eye', 'target' => null]
    ]
]])
