@include('components.datatable.actions', ['actions' => [
    'other' => [
      ['route' => "mailto:$item->email", 'title' => "Send an email to $item->first_name", 'icon' => 'envelope'],
      ['route' => route('impersonate', $item), 'title' => "Impersonate user", 'icon' => 'user-secret'],
      ['route' => route('admin.users.show', $item), 'title' => "More details", 'icon' => 'eye', 'target' => null]
    ],
    'delete' => route('admin.users.destroy', $item)
]])